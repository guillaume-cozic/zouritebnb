#!/usr/bin/env bash
# Importe les annonces Airbnb scrapées dans la table `accommodation`.
# Usage : ./import-rodrigues.sh            (génère le SQL puis l'exécute)
#         ./import-rodrigues.sh --dry-run  (génère le SQL sans l'exécuter)
# Prérequis : ./scrape-rodrigues.sh déjà lancé (dossier rodrigues-* présent).
set -euo pipefail

DIR=$(ls -dt rodrigues-*/ 2>/dev/null | head -1 || true)
[ -z "$DIR" ] && { echo "✗ Aucun dossier rodrigues-* — lance d'abord ./scrape-rodrigues.sh"; exit 1; }
DIR="${DIR%/}"
SQL="$DIR/import.sql"
echo "→ Source : $DIR/page.html"

REGION_ID="0x0000000000004000800000000000000A"   # région Rodrigues
TEAM_ID="0x00000000000040008000000000000001"     # team existante

python3 - "$DIR" "$REGION_ID" "$TEAM_ID" > "$SQL" <<'PY'
import re, json, sys, uuid, random

# --- Génération de données aléatoires pour les champs absents du scraping ---
# Bounding box approximatif de l'île Rodrigues.
LAT_MIN, LAT_MAX = -19.785, -19.660
LNG_MIN, LNG_MAX = 63.360, 63.505
VOIES = ["rue", "allée", "chemin", "impasse", "route"]
NOMS = ["des Cocotiers", "du Lagon", "Marivaux", "de la Plage", "Mont Lubin",
        "des Filaos", "Gabriel", "du Récif", "de l'Océan", "Cabri"]
AMENITIES = ["wifi", "air_conditioning", "kitchen", "parking", "tv", "pool",
             "sea_view", "mountain_view", "terrace", "balcony", "bbq", "garden",
             "washing_machine", "coffee_maker", "oven", "microwave", "iron",
             "towels", "bed_linen", "blankets", "extra_pillows", "walk_in_shower",
             "bathtub", "hot_tub", "outdoor_furniture", "books", "board_games",
             "streaming", "pets_allowed", "quiet_area"]

# Descriptions générées aléatoirement (le scraping ne fournit pas le texte).
DESC_INTRO = [
    "Découvrez ce logement chaleureux situé à {city}, sur l'île de Rodrigues.",
    "Bienvenue dans ce {type} niché à {city}, au cœur de Rodrigues.",
    "Offrez-vous une parenthèse de détente à {city}, sur la paisible île de Rodrigues.",
    "Ce {type} vous accueille à {city} pour un séjour authentique à Rodrigues.",
]
DESC_BODY = [
    "À quelques minutes des plages de sable blanc et du lagon turquoise, vous profiterez d'un cadre exceptionnel.",
    "Idéal pour explorer les sentiers de randonnée, les îlots et la faune préservée de l'île.",
    "Un havre de paix lumineux et confortable, parfait pour se ressourcer loin de l'agitation.",
    "Le logement allie confort moderne et charme créole pour un séjour réussi.",
]
DESC_OUTRO = [
    "L'endroit parfait pour des vacances en famille ou entre amis.",
    "Un point de départ idéal pour découvrir Rodrigues à votre rythme.",
    "Réservez dès maintenant pour vivre l'expérience rodriguaise.",
]

def random_description(type_, city):
    return " ".join([
        random.choice(DESC_INTRO).format(city=city or "Rodrigues",
                                         type=(type_ or "logement").lower()),
        random.choice(DESC_BODY),
        random.choice(DESC_OUTRO),
    ])

def random_fields(coord):
    single = random.randint(0, 3)
    double = random.randint(1, 3)
    return {
        # GPS scrapé si dispo, sinon aléatoire dans la bbox de Rodrigues.
        "latitude": coord.get("latitude") if coord.get("latitude") is not None
                    else round(random.uniform(LAT_MIN, LAT_MAX), 6),
        "longitude": coord.get("longitude") if coord.get("longitude") is not None
                     else round(random.uniform(LNG_MIN, LNG_MAX), 6),
        "single_beds": single,
        "double_beds": double,
        "bedrooms": max(1, random.randint(single + double - 1, single + double + 1)),
        "bathrooms": random.randint(1, 3),
        "max_guests": single + double * 2 + random.randint(0, 2),
        "street": f"{random.randint(1, 120)}, {random.choice(VOIES)} {random.choice(NOMS)}",
        "zip_code": f"{random.randint(10000, 99999)}",
        "check_in": random.choice(["14:00", "15:00", "16:00"]),
        "check_out": random.choice(["10:00", "11:00", "12:00"]),
        "amenities": json.dumps(random.sample(AMENITIES, random.randint(6, 12))),
    }

dir_, region_id, team_id = sys.argv[1], sys.argv[2], sys.argv[3]
html = open(f"{dir_}/page.html", encoding="utf-8").read()
m = re.search(r'<script id="data-deferred-state-0"[^>]*>(.*?)</script>', html, re.S)
if not m:
    sys.exit("✗ Blob JSON introuvable")
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

def sql_str(s):
    if s is None:
        return "NULL"
    return "'" + str(s).replace("\\", "\\\\").replace("'", "''") + "'"

def to_float(txt):
    # "1 234,56" / "101,74" -> float ; gère espaces fines insécables
    txt = re.sub(r"[^\d,]", "", txt.replace(" ", "").replace(" ", ""))
    return float(txt.replace(",", ".")) if txt else None

rows, skipped, seen = [], [], set()
for c in cards:
    dsl = c.get("demandStayListing") or {}
    enc = dsl.get("id", "")
    try:
        ref = __import__("base64").b64decode(enc).decode().split(":")[1]
    except Exception:
        continue
    if ref in seen:          # le JSON liste chaque annonce en double
        continue
    seen.add(ref)

    name = (c.get("nameLocalized") or {}).get("localizedStringWithTranslationPreference") \
           or c.get("subtitle") or "Logement Rodrigues"

    # prix/nuit : extrait du détail "N nuits x P €"
    price = None
    sdp = c.get("structuredDisplayPrice") or {}
    for g in (sdp.get("explanationData") or {}).get("priceDetails") or []:
        for it in g.get("items") or []:
            mm = re.search(r"x\s*([\d\s  ,]+)\s*€", it.get("description") or "")
            if mm:
                price = to_float(mm.group(1))
    if price is None:  # fallback : total affiché / 5 nuits
        pl = (sdp.get("primaryLine") or {})
        tot = to_float((pl.get("accessibilityLabel") or pl.get("price") or ""))
        price = round(tot / 5, 2) if tot else None
    if price is None:
        skipped.append(name)
        continue

    title = c.get("title") or ""
    city = title.split("⋅")[-1].strip() if "⋅" in title else None
    type_ = title.split("⋅")[0].strip() if "⋅" in title else None
    coord = (dsl.get("location") or {}).get("coordinate") or {}

    row = {
        "id": "0x" + uuid.uuid4().hex,
        "ref": ref,
        "title": name[:255],
        "description": random_description(type_, city),
        "price": price,
        "status": "published",
        "city": city,
        "country": "Île Rodrigues",
    }
    # Champs absents du scraping -> générés aléatoirement (GPS inclus si manquant).
    row.update(random_fields(coord))
    rows.append(row)

print("SET NAMES utf8mb4;")
print("START TRANSACTION;")
for r in rows:
    print(
        "INSERT INTO accommodation "
        "(id,title,description,price,status,city,country,latitude,longitude,"
        "bedrooms,bathrooms,max_guests,single_beds,double_beds,street,zip_code,"
        "check_in,check_out,amenities,region_id,team_id) VALUES ("
        f"{r['id']},{sql_str(r['title'])},{sql_str(r['description'])},{r['price']},"
        f"{sql_str(r['status'])},{sql_str(r['city'])},{sql_str(r['country'])},"
        f"{r['latitude']},{r['longitude']},"
        f"{r['bedrooms']},{r['bathrooms']},{r['max_guests']},"
        f"{r['single_beds']},{r['double_beds']},"
        f"{sql_str(r['street'])},{sql_str(r['zip_code'])},"
        f"{sql_str(r['check_in'])},{sql_str(r['check_out'])},"
        f"CAST({sql_str(r['amenities'])} AS JSON),"
        f"{region_id},{team_id});"
    )
print("COMMIT;")

# Table de correspondance uuid <-> réf. Airbnb : utilisée par les scripts
# import-rodrigues-photos.sh et enrich-rodrigues.sh (la description ne porte
# plus de marqueur, elle est désormais générée aléatoirement).
with open(f"{dir_}/mapping.tsv", "w", encoding="utf-8") as f:
    for r in rows:
        f.write(f"{r['id'][2:]}\t{r['ref']}\t{r['title']}\n")

sys.stderr.write(f"→ {len(rows)} INSERT générés"
                 + (f", {len(skipped)} ignorés (prix absent)" if skipped else "") + "\n")
PY

echo "→ SQL écrit dans $SQL"
if [ "${1:-}" = "--dry-run" ]; then
    echo "→ --dry-run : exécution sautée."
    exit 0
fi

echo "→ Insertion dans la base..."
docker compose -f API/docker-compose.yml exec -T mysql \
    mysql -uroot -proot --default-character-set=utf8mb4 app < "$SQL"

COUNT=$(docker compose -f API/docker-compose.yml exec -T mysql \
    mysql -uroot -proot -N -s --default-character-set=utf8mb4 app -e \
    "SELECT COUNT(*) FROM accommodation WHERE description LIKE '%depuis Airbnb%';" 2>/dev/null)
echo "✓ Import terminé — $COUNT logements Airbnb présents dans la table accommodation."
