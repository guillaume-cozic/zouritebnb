#!/usr/bin/env bash
# Télécharge une image Airbnb par logement Rodrigues importé et l'enregistre
# dans `accommodation_photo` + le fichier dans API/var/uploads/photos/.
# Usage : ./import-rodrigues-photos.sh
# Prérequis : ./scrape-rodrigues.sh et ./import-rodrigues.sh déjà lancés.
set -euo pipefail

DIR=$(ls -dt rodrigues-*/ 2>/dev/null | head -1 || true)
[ -z "$DIR" ] && { echo "✗ Aucun dossier rodrigues-* — lance d'abord ./scrape-rodrigues.sh"; exit 1; }
PHOTO_DIR="API/var/uploads/photos"
mkdir -p "$PHOTO_DIR"
DC="docker compose -f API/docker-compose.yml exec -T mysql mysql -uroot -proot --default-character-set=utf8mb4 -N -s app"

[ -f "$DIR/mapping.tsv" ] || { echo "✗ $DIR/mapping.tsv absent — relance ./import-rodrigues.sh"; exit 1; }

echo "→ Lecture des logements déjà photographiés..."
# Logements possédant déjà au moins une photo (pour ne pas en recréer).
$DC -e "SELECT LOWER(HEX(accommodation_id)) FROM accommodation_photo;" \
    2>/dev/null > "$DIR/_haspic.txt"

SQL="$DIR/import-photos.sql"
python3 - "$DIR" "$PHOTO_DIR" > "$SQL" <<'PY'
import re, json, sys, uuid, urllib.request, os

dir_, photo_dir = sys.argv[1], sys.argv[2]

# Map réf. Airbnb -> 1re URL d'image, depuis le blob JSON de la page.
html = open(f"{dir_}/page.html", encoding="utf-8").read()
m = re.search(r'<script id="data-deferred-state-0"[^>]*>(.*?)</script>', html, re.S)
data = json.loads(m.group(1))
cards = []
def walk(o):
    if isinstance(o, dict):
        if o.get("__typename") == "StaySearchResult":
            cards.append(o)
        for v in o.values():
            walk(v)
    elif isinstance(o, list):
        for v in o:
            walk(v)
walk(data)

import base64
pic_by_ref = {}
for c in cards:
    enc = (c.get("demandStayListing") or {}).get("id", "")
    try:
        ref = base64.b64decode(enc).decode().split(":")[1]
    except Exception:
        continue
    pics = [p.get("picture") for p in (c.get("contextualPictures") or []) if p.get("picture")]
    if ref not in pic_by_ref and pics:
        pic_by_ref[ref] = pics[0]

UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0"

def sql_str(s):
    return "'" + str(s).replace("\\", "\\\\").replace("'", "''") + "'"

has_pic = {l.strip() for l in open(f"{dir_}/_haspic.txt", encoding="utf-8") if l.strip()}

print("SET NAMES utf8mb4;")
print("START TRANSACTION;")
ok = miss = 0
for line in open(f"{dir_}/mapping.tsv", encoding="utf-8"):
    line = line.rstrip("\n")
    if not line:
        continue
    acc_hex, ref, title = line.split("\t", 2)
    if acc_hex in has_pic:          # déjà une photo -> on saute
        continue
    url = pic_by_ref.get(ref)
    if not url:
        miss += 1
        sys.stderr.write(f"  ⚠ pas d'image pour « {title} »\n")
        continue

    filename = f"{uuid.uuid4()}.jpg"
    dest = os.path.join(photo_dir, filename)
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=30) as r:
        content = r.read()
    with open(dest, "wb") as f:
        f.write(content)

    print(
        "INSERT INTO accommodation_photo "
        "(id,accommodation_id,filename,original_name,mime_type,size) VALUES ("
        f"0x{uuid.uuid4().hex},0x{acc_hex},{sql_str(filename)},"
        f"{sql_str(title[:251] + '.jpg')},'image/jpeg',{len(content)});"
    )
    ok += 1

print("COMMIT;")
sys.stderr.write(f"→ {ok} image(s) téléchargée(s)"
                 + (f", {miss} sans image" if miss else "") + "\n")
PY

if [ ! -s "$SQL" ] || ! grep -q INSERT "$SQL"; then
    echo "→ Aucune photo à insérer (déjà fait ?)."
    exit 0
fi

echo "→ Insertion dans accommodation_photo..."
docker compose -f API/docker-compose.yml exec -T mysql \
    mysql -uroot -proot --default-character-set=utf8mb4 app < "$SQL"

IDS=$(cut -f1 "$DIR/mapping.tsv" | sed 's/^/0x/' | paste -sd, -)
COUNT=$($DC -e "SELECT COUNT(*) FROM accommodation_photo WHERE accommodation_id IN ($IDS);" 2>/dev/null)
rm -f "$DIR/_haspic.txt"
echo "✓ Terminé — $COUNT photo(s) pour les logements Airbnb. Fichiers dans $PHOTO_DIR/"
