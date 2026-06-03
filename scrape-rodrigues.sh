#!/usr/bin/env bash
# Aspiration ponctuelle des annonces Airbnb pour Rodrigues.
# Usage   : ./scrape-rodrigues.sh
# Sortie  : rodrigues-<date>/annonces.json + annonces.csv
# Deps    : curl, python3
# NB: scraper Airbnb enfreint leurs CGU — usage perso/ponctuel uniquement,
#     ne pas boucler sous peine de blocage IP.
set -euo pipefail

URL='https://www.airbnb.fr/s/Rodrigues/homes?refinement_paths%5B%5D=%2Fhomes&date_picker_type=calendar&place_id=ChIJ-Wk8fjit4yMRdfYL8JMqT74&location_bb=wZ1XbEJ%2BAffBnivOQn1SXQ%3D%3D'
UA='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36'
OUT="rodrigues-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$OUT"

echo "→ Téléchargement de la page..."
curl -sSL \
  -H "User-Agent: $UA" \
  -H 'Accept-Language: fr-FR,fr;q=0.9' \
  --compressed \
  "$URL" -o "$OUT/page.html"

echo "→ Extraction et parsing..."
python3 - "$OUT" <<'PY'
import re, json, base64, csv, sys

out = sys.argv[1]
html = open(f"{out}/page.html", encoding="utf-8").read()

# Les données sont embarquées dans un blob JSON injecté pour l'hydratation React.
m = re.search(r'<script id="data-deferred-state-0"[^>]*>(.*?)</script>', html, re.S)
if not m:
    sys.exit("✗ Blob JSON introuvable — Airbnb a probablement servi une page anti-bot.")
data = json.loads(m.group(1))

# Récupère récursivement toutes les cartes de résultat.
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

def num_id(encoded):
    # base64("DemandStayListing:12345") -> "12345"
    try:
        return base64.b64decode(encoded).decode().split(":")[1]
    except Exception:
        return ""

rows = []
seen = set()
for c in cards:
    dsl = c.get("demandStayListing") or {}
    rid = num_id(dsl.get("id", ""))
    if not rid or rid in seen:
        continue
    seen.add(rid)
    coord = (dsl.get("location") or {}).get("coordinate") or {}
    price = ((c.get("structuredDisplayPrice") or {}).get("primaryLine") or {})
    rows.append({
        "id": rid,
        "nom": (c.get("nameLocalized") or {}).get("localizedStringWithTranslationPreference", ""),
        "type": c.get("title", ""),
        "note": c.get("avgRatingLocalized", ""),
        "prix": price.get("accessibilityLabel") or price.get("price", ""),
        "lat": coord.get("latitude"),
        "lng": coord.get("longitude"),
        "url": f"https://www.airbnb.fr/rooms/{rid}",
    })

json.dump(rows, open(f"{out}/annonces.json", "w"), ensure_ascii=False, indent=2)

with open(f"{out}/annonces.csv", "w", newline="", encoding="utf-8") as f:
    w = csv.DictWriter(f, fieldnames=rows[0].keys() if rows else
                       ["id","nom","type","note","prix","lat","lng","url"])
    w.writeheader()
    w.writerows(rows)

print(f"✓ {len(rows)} annonces → {out}/annonces.json + annonces.csv")
PY
