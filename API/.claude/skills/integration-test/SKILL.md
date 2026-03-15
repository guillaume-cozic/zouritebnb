---
name: integration-test
description: Generate integration tests for Doctrine repositories
argument-hint: <Repository FQCN or file path>
allowed-tools: Read, Glob, Grep, Write, Edit, Bash(docker exec*)
---

# Integration Test Generator for Doctrine Repositories

Generate PHPUnit integration tests for Doctrine repository implementations, testing the real database layer with MySQL via the Symfony kernel.

## Input

The user provides a Doctrine repository to test: `$ARGUMENTS`

If the argument is a file path, read it directly. If it's a class name, find the corresponding file under `src/**/Infrastructure/Doctrine/`.

## Steps

1. **Read the Doctrine repository** class to understand its methods and how it converts between domain and Doctrine entities.
2. **Read the domain port** (interface) that the repository implements to know the contract.
3. **Read the domain entity** to understand constructor arguments, types, and validation rules.
4. **Read the Doctrine entity** to understand the ORM mapping and field types.
5. **Check if the base test case** `RepositoryTestCase` exists at `tests/Integration/RepositoryTestCase.php`. If not, create it.
6. **Generate the test class** following the conventions below.
7. **Run the tests** with `docker exec -T api-php bin/phpunit tests/Integration/<Module>/` to verify they pass.

## File structure

```
tests/Integration/
├── RepositoryTestCase.php                              # Abstract base (shared)
└── <Module>/
    └── Infrastructure/
        └── Doctrine<Entity>RepositoryTest.php
```

## Conventions

### Base test case (`RepositoryTestCase`)

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class RepositoryTestCase extends KernelTestCase
{
    protected static ?bool $alwaysBootKernel = true;
}
```

- Extends `KernelTestCase` — boots the Symfony kernel for container access, no HTTP client
- `$alwaysBootKernel = true` to avoid deprecation
- DB isolation is handled by DAMA (`dama/doctrine-test-bundle`) — transaction rollback per test, already configured in `phpunit.dist.xml`
- No manual truncation or transaction management needed

### Test class

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\<Module>\Infrastructure;

use App\<Module>\Domain\Entity\<Entity>;
use App\<Module>\Domain\Port\<Entity>Repository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class Doctrine<Entity>RepositoryTest extends RepositoryTestCase
{
    private <Entity>Repository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(<Entity>Repository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $entity = new <Entity>(
            id: $id,
            // ... all required fields with valid values
        );

        $this->repository->save($entity);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        // Assert all fields match
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v4();
        $entity = new <Entity>(
            id: $id,
            // ... initial values
        );
        $this->repository->save($entity);

        $updated = new <Entity>(
            id: $id,
            // ... updated values
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        // Assert updated values
    }

    // Additional tests for module-specific repository methods
}
```

### Rules

- `declare(strict_types=1)` in every file
- Use `#[Before]` attribute instead of `setUp()` — consistent with unit and E2E test conventions
- Test method names: `test_should_<verb>_<description>` (snake_case)
- Use `self::assert*` for all assertions
- **Test via the domain port interface** — get the repository from the container by its port FQCN, not the Doctrine class
- **Create domain entities via their constructor** — do not use Doctrine entities or EntityManager directly in tests
- Use `Uuid::v4()` for test IDs (no need for `UuidGenerator::freeze()` since we control IDs directly)
- One test file per repository

### Test cases to cover

For every repository, always include:

| Test | Description |
|------|-------------|
| `test_should_save_and_find_by_id` | Round-trip: save a domain entity, findById, verify all fields match |
| `test_should_return_null_when_not_found` | findById with a random UUID returns null |
| `test_should_update_existing_entity` | Save twice with the same ID, verify the second save updates (not duplicates) |

Add module-specific tests when the repository has additional methods beyond `save` and `findById` (e.g. `findByStatus`, `delete`, `findAll`).