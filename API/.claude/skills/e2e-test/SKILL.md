---
name: e2e-test
description: Generate E2E tests for an API Platform route
argument-hint: <Output class FQCN, file path, or route URI>
allowed-tools: Read, Glob, Grep, Write, Edit, Bash(docker exec*)
---

# E2E Test Generator for API Platform Routes

Generate PHPUnit E2E tests that exercise the full HTTP → API Platform → Domain → Database flow for a given API route.

## Input

The user provides a route to test: `$ARGUMENTS`

This can be a file path to the Output class, a FQCN, or a route URI (e.g. `/api/accommodations`). Resolve it to the Output class that holds the `#[ApiResource]` attribute.

## Steps

1. **Read the Output class** to list all declared operations (Get, GetCollection, Post, Patch, Put, Delete), their `uriTemplate`, `input`, `processor`, and serialization groups.
2. **Read the Input class(es)** to understand which fields each write operation accepts.
3. **Read the Doctrine entity** (from `stateOptions`) to understand field types and which fields are exposed per group.
4. **Check if a base test case** already exists at `tests/E2e/<Slice>/<Slice>ApiTestCase.php`. If not, create one.
5. **Generate one test file per route** (not per operation group — one file per distinct HTTP method + URI combination).
6. **Run the tests** with `docker exec -T api-php bin/phpunit tests/E2e/<Slice>/`.

## File structure

```
tests/E2e/<Slice>/
├── <Slice>ApiTestCase.php           # Abstract base (shared helpers)
├── Create<Slice>Test.php            # POST /<slices>
├── Get<Slice>Test.php               # GET /<slices>/{id}
├── Get<Slice>CollectionTest.php     # GET /<slices>
├── Update<Slice>Test.php            # PATCH/PUT /<slices>/{id}
└── Delete<Slice>Test.php            # DELETE /<slices>/{id}
```

## Conventions

### Base test case

```php
<?php

declare(strict_types=1);

namespace App\Tests\E2e\<Slice>;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\<Slice>\Infrastructure\Doctrine\<SliceEntity>;
use Doctrine\ORM\EntityManagerInterface;

abstract class <Slice>ApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function insert<Slice>(string $field1, ...): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new <SliceEntity>()
            ->setField1($field1)
            // ...
            ;

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }
}
```

- Extends `ApiTestCase` from API Platform
- `$alwaysBootKernel = true` to avoid deprecation
- One `insert<Slice>(...)` helper per entity, returns the UUID string
- Uses EntityManager + Doctrine entity (not raw DBAL) for inserts
- DB isolation is handled by `dama/doctrine-test-bundle` (transaction rollback) — no manual truncation needed

### Test classes

Each test class is `final`, extends the base test case, and contains only tests for one route.

#### POST (create)

```php
final class Create<Slice>Test extends <Slice>ApiTestCase
{
    public function test_should_create_<slice>(): void
    {
        self::createClient()->request('POST', '/api/<slices>', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                // all required fields with valid values
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    public function test_should_return_<status>_when_<invalid_case>(): void
    {
        self::createClient()->request('POST', '/api/<slices>', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                // payload triggering domain validation error
            ],
        ]);

        self::assertResponseStatusCodeSame(<status>);
    }
}
```

#### GET (item)

```php
final class Get<Slice>Test extends <Slice>ApiTestCase
{
    public function test_should_get_<slice>(): void
    {
        $id = $this->insert<Slice>(...);

        self::createClient()->request('GET', '/api/<slices>/' . $id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            // all fields from the 'read' group
        ]);
    }

    public function test_should_return_404_when_not_found(): void
    {
        self::createClient()->request('GET', '/api/<slices>/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
```

#### GET (collection)

```php
final class Get<Slice>CollectionTest extends <Slice>ApiTestCase
{
    public function test_should_list_<slices>(): void
    {
        $this->insert<Slice>(...);
        $this->insert<Slice>(...);

        $response = self::createClient()->request('GET', '/api/<slices>');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_return_empty_collection(): void
    {
        $response = self::createClient()->request('GET', '/api/<slices>');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }
}
```

#### DELETE

```php
final class Delete<Slice>Test extends <Slice>ApiTestCase
{
    public function test_should_delete_<slice>(): void
    {
        $id = $this->insert<Slice>(...);

        self::createClient()->request('DELETE', '/api/<slices>/' . $id);

        self::assertResponseStatusCodeSame(204);
    }

    public function test_should_return_404_when_not_found(): void
    {
        self::createClient()->request('DELETE', '/api/<slices>/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
```

#### PATCH/PUT (update)

```php
final class Update<Slice>Test extends <Slice>ApiTestCase
{
    public function test_should_update_<slice>(): void
    {
        $id = $this->insert<Slice>(...);

        self::createClient()->request('PATCH', '/api/<slices>/' . $id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                // fields to update
            ],
        ]);

        self::assertResponseIsSuccessful();
    }

    public function test_should_return_404_when_not_found(): void
    {
        self::createClient()->request('PATCH', '/api/<slices>/00000000-0000-0000-0000-000000000000', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                // minimal valid payload
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
```

### Rules

- `declare(strict_types=1)` in every file
- Test method names: `test_should_<verb>_<description>` (snake_case)
- Use `self::createClient()` (not `static::createClient()`)
- Use `self::assert*` for all assertions
- POST/PUT requests must set `'Content-Type' => 'application/ld+json'` header
- PATCH requests must set `'Content-Type' => 'application/merge-patch+json'` header
- GET requests don't need a Content-Type header
- Use `assertResponseStatusCodeSame()` for error status codes
- Use `assertResponseIsSuccessful()` + `assertJsonContains()` for success with body
- Use `$response->toArray()['member']` to access hydra collection members (not `hydra:member`)
- JSON serializes float with no decimal part as int (e.g. `320.0` → `320`) — match this in assertions
- Only generate test files for operations that exist on the Output class
- One test file per route, one base test case per slice