#!/usr/bin/env bash
# Complète les logements Airbnb importés : remplit avec des données aléatoires
# toute colonne restée NULL (GPS, capacité, lits, équipements, adresse, horaires).
# Idempotent : COALESCE => ne remplace jamais une valeur déjà renseignée.
# Usage : ./enrich-rodrigues.sh
set -euo pipefail

DC_RAW="docker compose -f API/docker-compose.yml exec -T mysql mysql -uroot -proot"
DC="$DC_RAW --default-character-set=utf8mb4 -N -s app"

DIR=$(ls -dt rodrigues-*/ 2>/dev/null | head -1 || true)
[ -z "$DIR" ] || [ ! -f "${DIR}mapping.tsv" ] && {
    echo "✗ mapping.tsv introuvable — relance ./import-rodrigues.sh"; exit 1; }

echo "→ Lecture des logements Airbnb importés (depuis ${DIR}mapping.tsv)..."
cut -f1 "${DIR}mapping.tsv" > /tmp/_rodrigues_ids.txt

SQL=$(mktemp /tmp/enrich-rodrigues.XXXX.sql)
python3 - /tmp/_rodrigues_ids.txt > "$SQL" <<'PY'
import sys, random

# Bounding box approximatif de l'île Rodrigues (lat / lng).
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
CHECK_IN = ["14:00", "15:00", "16:00"]
CHECK_OUT = ["10:00", "11:00", "12:00"]

ids = [l.strip() for l in open(sys.argv[1]) if l.strip()]

def sql_str(s):
    return "'" + str(s).replace("\\", "\\\\").replace("'", "''") + "'"

print("SET NAMES utf8mb4;")
print("START TRANSACTION;")
for acc in ids:
    single = random.randint(0, 3)
    double = random.randint(1, 3)
    bedrooms = max(1, random.randint(single + double - 1, single + double + 1))
    bathrooms = random.randint(1, 3)
    guests = single + double * 2 + random.randint(0, 2)
    lat = round(random.uniform(LAT_MIN, LAT_MAX), 6)
    lng = round(random.uniform(LNG_MIN, LNG_MAX), 6)
    street = f"{random.randint(1, 120)}, {random.choice(VOIES)} {random.choice(NOMS)}"
    zip_code = f"{random.randint(10000, 99999)}"
    amen = random.sample(AMENITIES, random.randint(6, 12))
    amen_json = "[" + ",".join('"%s"' % a for a in amen) + "]"

    # COALESCE : n'écrit que si la colonne est NULL.
    print(
        f"UPDATE accommodation SET "
        f"latitude=COALESCE(latitude,{lat}), "
        f"longitude=COALESCE(longitude,{lng}), "
        f"bedrooms=COALESCE(bedrooms,{bedrooms}), "
        f"bathrooms=COALESCE(bathrooms,{bathrooms}), "
        f"max_guests=COALESCE(max_guests,{guests}), "
        f"single_beds=COALESCE(single_beds,{single}), "
        f"double_beds=COALESCE(double_beds,{double}), "
        f"street=COALESCE(street,{sql_str(street)}), "
        f"zip_code=COALESCE(zip_code,{sql_str(zip_code)}), "
        f"check_in=COALESCE(check_in,{sql_str(random.choice(CHECK_IN))}), "
        f"check_out=COALESCE(check_out,{sql_str(random.choice(CHECK_OUT))}), "
        f"amenities=COALESCE(amenities,CAST({sql_str(amen_json)} AS JSON)) "
        f"WHERE id=0x{acc};"
    )
print("COMMIT;")
sys.stderr.write(f"→ {len(ids)} logement(s) à enrichir\n")
PY

echo "→ Application des données aléatoires..."
$DC_RAW --default-character-set=utf8mb4 app < "$SQL"

IDS=$(sed 's/^/0x/' /tmp/_rodrigues_ids.txt | paste -sd, -)
rm -f "$SQL" /tmp/_rodrigues_ids.txt

echo "✓ Terminé. Aperçu :"
$DC_RAW --default-character-set=utf8mb4 app -e "
  SELECT title,bedrooms,bathrooms,max_guests,
         ROUND(latitude,4) lat,ROUND(longitude,4) lng,check_in,check_out
  FROM accommodation WHERE id IN ($IDS) LIMIT 5;" 2>/dev/null
