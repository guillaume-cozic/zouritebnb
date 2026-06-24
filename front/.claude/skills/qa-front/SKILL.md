---
name: qa-front
description: Navigue automatiquement sur tout le front (anonyme / voyageur / hôte) avec un vrai navigateur, collecte les erreurs runtime et produit un rapport QA priorisé des bugs et améliorations.
argument-hint: "[anon|guest|host] (optionnel — sinon les trois personas)"
allowed-tools: Read, Bash, Glob, Grep
---

# QA Front — exploration navigateur + rapport

Tu es un·e ingénieur·e QA. Tu pilotes un vrai Chromium sur l'app front, tu visites
chaque route avec chaque persona, tu collectes les signaux runtime, **puis tu juges**
ce qui est un bug réel vs un faux positif, et ce qui mérite une amélioration UX.

Le crawler (`crawl.mjs`) ne fait que **récolter** : erreurs console, exceptions non
catchées, requêtes réseau ≥ 400, images cassées, alt manquants, captures d'écran.
C'est **toi** qui analyses, écartes le bruit, et rédiges le rapport.

## Personas (comptes de démo, env dev)

| Persona | Compte | Mot de passe | Couvre |
|---------|--------|--------------|--------|
| `anon`  | — | — | home, listing, fiches logement/projet, login, register |
| `guest` | `qa.voyageur@example.com` (créé au 1er run) | `password` | + espace voyageur, réservation, messagerie, profil |
| `host`  | `marie.hote@example.com` (seedé) | `password` | + back-office hôte, calendrier, équipe, édition logement |

Le `guest` est un compte jetable que le crawler **enregistre automatiquement** s'il
n'existe pas (états vides, mais déterministe). Pour un QA voyageur avec des données
riches, surcharge `QA_GUEST_EMAIL` + `QA_PASSWORD` avec un compte seedé connu.
Le `host` doit rester `marie.hote@example.com` car il possède les logements
nécessaires au back-office.

## Procédure

### 1. Vérifier que la stack tourne

```bash
curl -sf -o /dev/null http://localhost:3000 && echo "front OK" || echo "front DOWN"
curl -sf -o /dev/null http://localhost:8080/api/docs && echo "api OK" || echo "api DOWN"
```

Si l'un est down : `./start.sh` (depuis la racine), puis attendre que les deux répondent.

### 2. Seeder les données de démo (pour que voyageur/hôte aient du contenu)

```bash
docker compose -f API/docker-compose.yml exec -T php bin/console app:seed:demo-trips
```

(Idempotent. Crée réservations/voyages pour les comptes de démo.)

### 3. Installer les déps du crawler (isolées, une seule fois)

```bash
cd front/.claude/skills/qa-front
npm install --silent
npx playwright install chromium
```

### 4. Lancer le crawl

```bash
cd front/.claude/skills/qa-front
QA_OUT="$PWD/qa-report" node crawl.mjs
```

Variables surchargeables : `QA_FRONT_URL` (def. `http://localhost:3000`),
`QA_API_URL` (def. `http://localhost:8080`), `QA_PASSWORD`, `QA_GUEST_EMAIL`,
`QA_HOST_EMAIL`, `QA_NAV_TIMEOUT`.

Pour ne tester qu'un persona, restreins en éditant la liste `personas` ou en
filtrant à l'analyse — par défaut, crawl complet.

### 5. Analyser

1. **Lis** `qa-report/report.json` (commence par `summary`, puis les pages avec
   `consoleErrors`, `pageErrors`, `failedRequests`, `brokenImages`, `errorBanner`,
   `navError`, ou `redirected` inattendu).
2. **Regarde les captures** des pages suspectes (`qa-report/<persona>__<route>.png`)
   avec l'outil Read — c'est là que se voient les soucis visuels (chevauchement,
   débordement, état vide, contraste, libellés tronqués) qu'aucune métrique ne capte.
3. **Filtre le bruit** : ignore les 401 attendus sur endpoints protégés en anon,
   les warnings React tiers connus, les images de démo absentes. Garde ce qui
   casse une vraie page utilisateur.

### 6. Rédiger le rapport

Produis un rapport Markdown directement dans la réponse (pas de fichier sauf demande),
trié par sévérité :

```
## Rapport QA front — <date>

### 🔴 Bloquant (page cassée / crash / fonctionnalité inutilisable)
- **[persona] route** — symptôme. Preuve : <erreur console / 500 / capture>. Piste : <fichier/cause probable>.

### 🟠 Majeur (dégradé mais contournable)
### 🟡 Mineur / UX (amélioration, a11y, cohérence visuelle)

### ✅ Pages OK
<liste compacte des routes sans souci>
```

Règles de rédaction :
- Une ligne = un problème actionnable, avec **preuve** (citation de l'erreur ou capture) et **piste de correction** (pointe le composant/route quand tu peux le déduire — cf. `src/App.tsx` pour le mapping route → composant).
- Pas d'invention : si un signal est ambigu, dis-le et propose de vérifier manuellement.
- Distingue clairement **bug** (à corriger) de **amélioration** (suggestion).

## Notes

- Le crawler s'authentifie côté API puis injecte `auth.user` / `auth.token` dans le
  `localStorage` avant navigation — même mécanisme que l'app.
- `node_modules/` et `qa-report/` sont gitignorés : seuls le script, le `package.json`
  et ce SKILL sont versionnés.
