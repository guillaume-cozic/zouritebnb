# Skill: API Discovery

## Quand utiliser

**Avant** de coder toute feature qui interagit avec le backend. Cela inclut : création d'un slice, d'un thunk, de types, ou de tout composant qui affiche ou modifie des données venant de l'API.

## Procédure obligatoire

### 1. Consulter la documentation OpenAPI

Avant d'écrire le moindre code, récupérer la spec de l'API :

```bash
curl -s -H "Accept: application/vnd.openapi+json" http://localhost:8080/api/docs
```

### 2. Identifier les endpoints concernés

Pour chaque feature, relever :
- Les **paths** et **méthodes HTTP** disponibles
- Les **request bodies** attendus (champs, types, champs obligatoires)
- Les **responses** (codes HTTP, schémas de réponse)
- Les **paramètres** (path params, query params, pagination)
- Le **content-type** utilisé (`application/ld+json`, `application/merge-patch+json`, etc.)

### 3. En déduire les types TypeScript

Les interfaces dans `features/<feature>/<Feature>Types.ts` doivent correspondre **exactement** aux schémas de la spec OpenAPI. Ne jamais inventer de champs, ne jamais en omettre.

### 4. En déduire les thunks

Chaque endpoint utilisé par la feature doit avoir un `createAsyncThunk` correspondant, avec :
- La bonne méthode HTTP
- Le bon content-type dans les headers
- Le bon format de body
- La gestion des codes d'erreur documentés (400, 404, 422)

### 5. Adapter les selectors aux réponses Hydra

L'API retourne du JSON-LD / Hydra. Les collections sont dans `hydra:member`, la pagination dans `hydra:view`. En tenir compte dans les thunks et le state shape :

```ts
// Extraction des items d'une collection Hydra
const response = await api.get('/api/accommodations');
return response.data['hydra:member'];   // les items
// response.data['hydra:totalItems']    // le total
// response.data['hydra:view']          // la pagination
```

## Exemple complet

Pour la feature "accommodation", la consultation de l'API révèle :

| Endpoint | Méthode | Content-Type | Usage |
|----------|---------|-------------|-------|
| `/api/accommodations` | GET | `application/ld+json` | Liste paginée |
| `/api/accommodations` | POST | `application/ld+json` | Création |
| `/api/accommodations/{id}` | GET | `application/ld+json` | Détail |
| `/api/accommodations/{id}/price` | PATCH | `application/merge-patch+json` | Modifier le prix |
| `/api/accommodations/{id}/publish` | PATCH | `application/merge-patch+json` | Publier |

Cela donne les thunks : `fetchAccommodations`, `createAccommodation`, `fetchAccommodation`, `updateAccommodationPrice`, `publishAccommodation`, etc.

## Règles

1. **Ne jamais coder un thunk sans avoir consulté l'API** — les endpoints, champs et content-types doivent venir de la spec, pas de suppositions
2. **Si un endpoint n'existe pas dans la spec, ne pas l'inventer** — signaler le manque à l'utilisateur
3. **Toujours vérifier le content-type** — `application/ld+json` pour POST/PUT, `application/merge-patch+json` pour PATCH