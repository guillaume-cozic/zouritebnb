# Contract testing (OpenAPI = source of truth)

The backend's OpenAPI schema is the **single contract** between the API and the
front. It lives at `API/openapi.json` and is generated from the API Platform
resources. Both sides are pinned to it:

```
                 API/openapi.json  (generated from API Platform)
                         │  source of truth
            ┌────────────┴────────────┐
        BACK (provider)           FRONT (consumer)
   E2E responses are validated   TS types are generated from
   against the schema            the schema; a tsc guard pins
                                 hand-written types to it
```

## The golden rule

**Whenever the API surface changes, regenerate the contract:**

```bash
# 1. back: re-export the contract
docker exec -i api-php php bin/console api:openapi:export --output=openapi.json

# 2. front: regenerate the TS types from it
cd front && npm run gen:api-types
```

Commit `API/openapi.json` and `front/src/api/schema.ts` together with the change.
Both regenerations are idempotent — re-running with no API change yields no diff,
so a CI step can fail the build if either file is stale (regenerate + `git diff --exit-code`).

## Provider side (back)

E2E tests assert that real API responses conform to the published schema, via the
`App\Tests\E2e\AssertsOpenApiContract` trait (uses `opis/json-schema`, which speaks
JSON Schema draft 2020-12 = the OpenAPI 3.1 dialect):

```php
use App\Tests\E2e\AssertsOpenApiContract;

$response = static::createClient()->request('GET', '/api/accommodations/'.$id);
$this->assertResponseMatchesOpenApiContract($response, 'GET', '/api/accommodations/{id}');
```

Pass the **templated** path (`/api/accommodations/{id}`), not the concrete URI.
The test fails if the implementation drifts from the documented schema — until
`openapi.json` is regenerated.

Run: `docker exec -i api-php php bin/phpunit tests/E2e`

## Consumer side (front)

- `front/src/api/schema.ts` — generated types (do not edit by hand).
- `front/src/api/contract.ts` — compile-time guard pinning the hand-written
  domain types (e.g. `AccommodationListItem`) to the generated contract. If the
  backend renames/removes a field or changes its type, `tsc` fails here.

Run the gate: `cd front && npm run typecheck`

> Note: CRA's `build`/`test` only type-check files reachable from imports, so the
> guard is enforced by `npm run typecheck` (`tsc --noEmit`, which scans all files).
> Add it to CI.