# CLAUDE.md

## Monorepo Structure

```
bnb/
├── API/    # Backend Symfony / API Platform
├── front/  # Frontend React / Redux / TypeScript (voyageurs + backoffice hôtes)
└── admin/  # Frontend React / Vite / Redux / TypeScript — back-office administrateur plateforme (lecture seule, ROLE_ADMIN)
```

## Skills

Skills are split across both projects. **Always consult the relevant skills before writing code.**

### Backend — `API/.claude/skills/`

| Skill | When to use |
|-------|-------------|
| `hexagonal-architecture` | Any code change — reference for layers, dependencies, file structure |
| `domain-code` | Value objects, entities, exceptions, domain logic |
| `api-processor` | Exposing a use case as a POST/PATCH/PUT/DELETE endpoint |
| `e2e-test` | Writing E2E tests for API routes |
| `integration-test` | Writing integration tests for Doctrine repositories |
| `unit-test` | Writing unit tests for use cases |
| `openapi-doc` | Enriching OpenAPI descriptions, examples, summaries |
| `contract-test` | OpenAPI contract tests between API and front (provider + consumer) |
| `feature-team` | Implementing a full vertical slice (spawns 3 agents) |

### Frontend — `front/.claude/skills/`

| Skill | When to use |
|-------|-------------|
| `react-redux-architecture` | Any component, slice, or business logic in the front app |
| `api-discovery` | Before coding any feature that interacts with the backend API |
| `event-driven-components` | Any handler/`useEffect` that would dispatch more than one action — components dispatch a single intent event, slices/listeners react |
| `redux-store-test` | Testing a slice/listener — dispatch an action, assert the store state |

## Orchestration

When developing a full-stack feature:

1. **Read the relevant backend skills** in `API/.claude/skills/`
2. **Read the relevant frontend skills** in `front/.claude/skills/`
3. **Consult the API** (`curl -s -H "Accept: application/vnd.openapi+json" http://localhost:8080/api/docs`) before writing any frontend code that calls the backend
4. Implement backend first (domain → infrastructure → API), then frontend (types → slice → components)

## Commands

### Toute la stack (Docker)

Tout tourne en conteneur (Node 24, PHP 8.5) — aucun runtime local requis, build identique sur chaque machine.

```bash
./start.sh            # build + up -d de tout (API + front + admin + blog)
./start.sh stop       # down
./start.sh logs front # suivre les logs d'un service
./start.sh status     # docker compose ps
```

| Service | URL | Stack |
|---------|-----|-------|
| API | http://localhost:8080 | Symfony / PHP 8.5 (php/nginx/mysql) |
| front | http://localhost:3000 | React 19 + Vite 6 + Vitest |
| admin | http://localhost:3001 | React 18 + Vite 6 (ROLE_ADMIN) |
| blog | http://localhost:4321/blog/ | Astro 5 |

Le `docker-compose.yml` racine inclut `API/docker-compose.yml` et ajoute les trois
apps Node en conteneurs de dev (hot-reload : source bind-mountée, `node_modules`
de l'image préservé par un volume anonyme).

### API (inside Docker)

```bash
docker compose -f API/docker-compose.yml exec php bin/phpunit         # Tests
docker compose -f API/docker-compose.yml exec php bin/console cache:clear
```

### Front (hors Docker, si besoin)

```bash
cd front && npm start        # Vite dev server (localhost:3000)
cd front && npm run build    # tsc --noEmit && vite build
cd front && npm test         # Vitest
```

### Admin

```bash
cd admin && npm run dev    # Dev server (localhost:3001)
cd admin && npm run build  # tsc --noEmit && vite build
```

L'app admin suit les mêmes conventions que `front/` (slices Redux, selectors, composants déclaratifs, tokens Tailwind sémantiques). Elle consomme les endpoints `/api/admin/*` (GET, sécurisés `ROLE_ADMIN`).
