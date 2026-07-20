#!/usr/bin/env bash
# Importe les annonces Airbnb de Rodrigues (logements + photos) DANS LA PROD.
#
# À exécuter SUR LE VPS, depuis ~/zouritebnb (là où vivent docker-compose.prod.yml
# et .env.prod.local). Le script scrape Airbnb, télécharge les images à
# l'exécution, les pousse dans le volume var/uploads/photos du conteneur php,
# puis insère logements + photos + galerie dans la base de production.
#
# Usage :
#   ./import-rodrigues-prod.sh            # importe
#   ./import-rodrigues-prod.sh --dry-run  # génère le SQL sans rien pousser
#
# Idempotent : UUID déterministes (dérivés de la réf Airbnb) + upsert, donc
# relançable sans créer de doublon.
#
# NB: scraper Airbnb enfreint leurs CGU — usage perso/ponctuel, ne pas boucler.
set -euo pipefail

DRY_RUN=false
[ "${1:-}" = "--dry-run" ] && DRY_RUN=true

COMPOSE_FILE="docker-compose.prod.yml"
COMPOSE="docker compose -f $COMPOSE_FILE"
PHOTO_DEST="/var/www/html/var/uploads/photos"

command -v docker >/dev/null || { echo "✗ docker introuvable"; exit 1; }
command -v python3 >/dev/null || { echo "✗ python3 requis (parsing + téléchargement)"; exit 1; }
command -v curl >/dev/null || { echo "✗ curl requis (scraping)"; exit 1; }
[ -f "$COMPOSE_FILE" ] || { echo "✗ $COMPOSE_FILE absent — lance ce script depuis ~/zouritebnb"; exit 1; }
[ -f .env.prod.local ] || { echo "✗ .env.prod.local absent"; exit 1; }

# Identifiants MySQL de prod.
set -a; . ./.env.prod.local; set +a
: "${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD absent de .env.prod.local}"
DB="${MYSQL_DATABASE:-app}"

WORK="$(mktemp -d)"
PHOTO_TMP="$WORK/photos"; mkdir -p "$PHOTO_TMP"
trap 'rm -rf "$WORK"' EXIT

URL='https://www.airbnb.fr/s/Rodrigues/homes?refinement_paths%5B%5D=%2Fhomes&date_picker_type=calendar&place_id=ChIJ-Wk8fjit4yMRdfYL8JMqT74&location_bb=wZ1XbEJ%2BAffBnivOQn1SXQ%3D%3D'
UA='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36'

echo "→ Scraping de la page Airbnb..."
curl -sSL -H "User-Agent: $UA" -H 'Accept-Language: fr-FR,fr;q=0.9' --compressed "$URL" -o "$WORK/page.html"

echo "→ Parsing, téléchargement des images et génération du SQL..."
python3 - "$WORK/page.html" "$PHOTO_TMP" > "$WORK/import.sql" <<'PY'
import re, json, sys, uuid, base64, random, urllib.request, os

page, photo_tmp = sys.argv[1], sys.argv[2]

# UUID déterministes : mêmes namespaces que le seed de dev (app:seed:rodrigues).
NS_ACC = uuid.UUID('0ade5eed-0002-7000-8000-000000000000')
NS_PIC = uuid.UUID('0ade5eed-0003-7000-8000-000000000000')
TEAM_UUID   = '0ade5eed-0002-7000-8000-000000000001'   # team de démo (hôte Rodrigues)
REGION_UUID = '00000000-0000-4000-8000-00000000000a'   # région Rodrigues
UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0"

LAT_MIN, LAT_MAX = -19.785, -19.660
LNG_MIN, LNG_MAX = 63.360, 63.505
VOIES = ["rue", "allée", "chemin", "impasse", "route"]
NOMS = ["des Cocotiers","du Lagon","Marivaux","de la Plage","Mont Lubin","des Filaos",
        "Gabriel","du Récif","de l'Océan","Cabri"]
AMENITIES = ["wifi","air_conditioning","kitchen","parking","tv","pool","sea_view",
             "mountain_view","terrace","balcony","bbq","garden","washing_machine",
             "coffee_maker","oven","microwave","iron","towels","bed_linen","blankets",
             "extra_pillows","walk_in_shower","bathtub","hot_tub","outdoor_furniture",
             "books","board_games","streaming","pets_allowed","quiet_area"]
DESC_INTRO = [
 "Découvrez ce logement chaleureux situé à {city}, sur l'île de Rodrigues.",
 "Bienvenue dans ce {type} niché à {city}, au cœur de Rodrigues.",
 "Offrez-vous une parenthèse de détente à {city}, sur la paisible île de Rodrigues.",
 "Ce {type} vous accueille à {city} pour un séjour authentique à Rodrigues."]
DESC_BODY = [
 "À quelques minutes des plages de sable blanc et du lagon turquoise, vous profiterez d'un cadre exceptionnel.",
 "Idéal pour explorer les sentiers de randonnée, les îlots et la faune préservée de l'île.",
 "Un havre de paix lumineux et confortable, parfait pour se ressourcer loin de l'agitation.",
 "Le logement allie confort moderne et charme créole pour un séjour réussi."]
DESC_OUTRO = [
 "L'endroit parfait pour des vacances en famille ou entre amis.",
 "Un point de départ idéal pour découvrir Rodrigues à votre rythme.",
 "Réservez dès maintenant pour vivre l'expérience rodriguaise."]

def map_type(t):
    t = t.lower()
    if "villa" in t: return "villa"
    if "bungalow" in t or "tiny" in t: return "bungalow"
    if "studio" in t: return "studio"
    if "chambre" in t or "room" in t: return "room"
    if "appartement" in t or "apart" in t: return "apartment"
    return "house"

def to_float(txt):
    txt = re.sub(r"[^\d,]", "", txt.replace(" ", "").replace(" ", "").replace(" ", ""))
    return float(txt.replace(",", ".")) if txt else None

def sql_str(s):
    return "'" + str(s).replace("\\", "\\\\").replace("'", "''") + "'"

def hexid(u):
    return "0x" + uuid.UUID(u).hex

html = open(page, encoding="utf-8").read()
m = re.search(r'<script id="data-deferred-state-0"[^>]*>(.*?)</script>', html, re.S)
if not m:
    sys.exit("✗ Blob JSON introuvable — page anti-bot probable.")
data = json.loads(m.group(1))

cards = []
def walk(o):
    if isinstance(o, dict):
        if o.get("__typename") == "StaySearchResult": cards.append(o)
        for v in o.values(): walk(v)
    elif isinstance(o, list):
        for v in o: walk(v)
walk(data)

rows, photos_by_acc, seen = [], {}, set()
for c in cards:
    dsl = c.get("demandStayListing") or {}
    try:
        ref = base64.b64decode(dsl.get("id", "")).decode().split(":")[1]
    except Exception:
        continue
    if ref in seen: continue
    seen.add(ref)
    rnd = random.Random(ref)

    name = (c.get("nameLocalized") or {}).get("localizedStringWithTranslationPreference") \
           or c.get("subtitle") or "Logement Rodrigues"
    price = None
    sdp = c.get("structuredDisplayPrice") or {}
    for g in (sdp.get("explanationData") or {}).get("priceDetails") or []:
        for it in g.get("items") or []:
            mm = re.search(r"x\s*([\d\s  ,]+)\s*€", it.get("description") or "")
            if mm: price = to_float(mm.group(1))
    if price is None:
        pl = sdp.get("primaryLine") or {}
        tot = to_float((pl.get("accessibilityLabel") or pl.get("price") or ""))
        price = round(tot / 5, 2) if tot else None
    if price is None:
        continue

    title = c.get("title") or ""
    city = title.split("⋅")[-1].strip() if "⋅" in title else "Rodrigues"
    type_ = map_type(title.split("⋅")[0].strip() if "⋅" in title else "logement")
    coord = (dsl.get("location") or {}).get("coordinate") or {}
    single, double = rnd.randint(0, 3), rnd.randint(1, 3)
    acc_id = str(uuid.uuid5(NS_ACC, ref))
    rows.append({
        "id": acc_id, "title": name[:255],
        "description": " ".join([rnd.choice(DESC_INTRO).format(city=city, type=type_),
                                 rnd.choice(DESC_BODY), rnd.choice(DESC_OUTRO)]),
        "price": price, "city": city, "country": "Île Rodrigues", "type": type_,
        "latitude": coord.get("latitude") if coord.get("latitude") is not None else round(rnd.uniform(LAT_MIN, LAT_MAX), 6),
        "longitude": coord.get("longitude") if coord.get("longitude") is not None else round(rnd.uniform(LNG_MIN, LNG_MAX), 6),
        "single_beds": single, "double_beds": double,
        "bedrooms": max(1, rnd.randint(single + double - 1, single + double + 1)),
        "bathrooms": rnd.randint(1, 3), "max_guests": single + double * 2 + rnd.randint(0, 2),
        "street": f"{rnd.randint(1, 120)}, {rnd.choice(VOIES)} {rnd.choice(NOMS)}",
        "zip_code": f"{rnd.randint(10000, 99999)}",
        "check_in": rnd.choice(["14:00", "15:00", "16:00"]),
        "check_out": rnd.choice(["10:00", "11:00", "12:00"]),
        "amenities": json.dumps(rnd.sample(AMENITIES, rnd.randint(6, 12))),
    })

    # Photos : téléchargées à l'exécution dans photo_tmp, nommées par UUID déterministe.
    pics = [p.get("picture") for p in (c.get("contextualPictures") or []) if p.get("picture")]
    plist = []
    for n, purl in enumerate(pics):
        pid = str(uuid.uuid5(NS_PIC, f"{ref}:{n}"))
        fname = f"{pid}.jpg"
        try:
            req = urllib.request.Request(purl, headers={"User-Agent": UA})
            content = urllib.request.urlopen(req, timeout=30).read()
        except Exception as e:
            sys.stderr.write(f"  ⚠ image {ref}:{n} échouée ({e})\n"); continue
        open(os.path.join(photo_tmp, fname), "wb").write(content)
        plist.append({"id": pid, "filename": fname, "original": (name[:200] + ".jpg"), "size": len(content)})
    if plist:
        photos_by_acc[acc_id] = plist

acc_ids_sql = ",".join(hexid(r["id"]) for r in rows)

print("SET NAMES utf8mb4;")
print("START TRANSACTION;")

# Team de démo (l'hôte propriétaire des logements). Idempotent.
print(f"INSERT INTO team (id) VALUES ({hexid(TEAM_UUID)}) "
      f"ON DUPLICATE KEY UPDATE id = id;")

for r in rows:
    print(
        "INSERT INTO accommodation "
        "(id,title,description,price,status,city,country,type,latitude,longitude,"
        "bedrooms,bathrooms,max_guests,single_beds,double_beds,street,zip_code,"
        "check_in,check_out,amenities,region_id,team_id) VALUES ("
        f"{hexid(r['id'])},{sql_str(r['title'])},{sql_str(r['description'])},{r['price']},"
        f"'published',{sql_str(r['city'])},{sql_str(r['country'])},{sql_str(r['type'])},"
        f"{r['latitude']},{r['longitude']},{r['bedrooms']},{r['bathrooms']},{r['max_guests']},"
        f"{r['single_beds']},{r['double_beds']},{sql_str(r['street'])},{sql_str(r['zip_code'])},"
        f"{sql_str(r['check_in'])},{sql_str(r['check_out'])},"
        f"CAST({sql_str(r['amenities'])} AS JSON),{hexid(REGION_UUID)},{hexid(TEAM_UUID)}) "
        "ON DUPLICATE KEY UPDATE title=VALUES(title),description=VALUES(description),"
        "price=VALUES(price),status=VALUES(status),city=VALUES(city),type=VALUES(type),"
        "latitude=VALUES(latitude),longitude=VALUES(longitude),amenities=VALUES(amenities),"
        "region_id=VALUES(region_id),team_id=VALUES(team_id);"
    )

# Photos + galerie : on repart de zéro pour ces logements (évite les doublons au re-run).
if acc_ids_sql:
    print(f"DELETE FROM accommodation_photo WHERE accommodation_id IN ({acc_ids_sql});")
    print(f"DELETE FROM accommodation_gallery WHERE accommodation_id IN ({acc_ids_sql});")
for acc_id, plist in photos_by_acc.items():
    for p in plist:
        print(
            "INSERT INTO accommodation_photo "
            "(id,accommodation_id,filename,original_name,mime_type,size) VALUES ("
            f"{hexid(p['id'])},{hexid(acc_id)},{sql_str(p['filename'])},"
            f"{sql_str(p['original'])},'image/jpeg',{p['size']});"
        )
    ids_json = json.dumps([p["id"] for p in plist])
    print(
        "INSERT INTO accommodation_gallery (accommodation_id,photo_ids) VALUES ("
        f"{hexid(acc_id)},CAST({sql_str(ids_json)} AS JSON)) "
        "ON DUPLICATE KEY UPDATE photo_ids=VALUES(photo_ids);"
    )

print("COMMIT;")
total_pics = sum(len(v) for v in photos_by_acc.values())
sys.stderr.write(f"→ {len(rows)} logements, {total_pics} photos préparés\n")
PY

echo "→ SQL généré ($(grep -c 'INSERT INTO accommodation ' "$WORK/import.sql") logements, $(grep -c 'INSERT INTO accommodation_photo' "$WORK/import.sql") photos)."

if [ "$DRY_RUN" = true ]; then
    echo "→ --dry-run : le SQL est dans $WORK/import.sql, rien n'est poussé."
    cp "$WORK/import.sql" ./import-rodrigues-prod.sql
    echo "→ copie conservée : ./import-rodrigues-prod.sql"
    exit 0
fi

echo "→ Copie des images dans le conteneur php ($PHOTO_DEST)..."
$COMPOSE exec -T php mkdir -p "$PHOTO_DEST"
$COMPOSE cp "$PHOTO_TMP/." "php:$PHOTO_DEST/"
# Lisibles par www-data (docker cp copie en root).
$COMPOSE exec -T php sh -c "chmod 644 $PHOTO_DEST/*.jpg" || true

echo "→ Insertion en base de production..."
$COMPOSE exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" \
    --default-character-set=utf8mb4 "$DB" < "$WORK/import.sql"

COUNT=$($COMPOSE exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -s "$DB" \
    -e "SELECT COUNT(*) FROM accommodation WHERE team_id=$(python3 -c "import uuid;print('0x'+uuid.UUID('0ade5eed-0002-7000-8000-000000000001').hex)");" 2>/dev/null)
PICS=$($COMPOSE exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -s "$DB" \
    -e "SELECT COUNT(*) FROM accommodation_photo;" 2>/dev/null)
echo "✓ Import prod terminé — $COUNT logements de démo, $PICS photos en base."
