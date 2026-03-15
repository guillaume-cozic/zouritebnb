---
name: hexagonal-architecture
description: Reference skill for the project's hexagonal + vertical slice architecture conventions
argument-hint: <module name or question about architecture>
allowed-tools: Read, Glob, Grep
---

# Hexagonal Architecture Reference

This skill is a reference for the project's architecture conventions. It is used by other skills (e.g. `feature-team`) and can be invoked directly to answer architecture questions.

## Input

`$ARGUMENTS` — a module name to review, or a question about architecture conventions.

If a module name is given, check that it follows the conventions below. If a question, answer it using the rules documented here.

---

## Layer model

Each module follows **hexagonal architecture** (ports & adapters) with 3 layers:

```
src/<Module>/
├── Domain/            ← Pure business logic, zero framework dependency
├── Application/       ← Use cases orchestrating the domain, zero framework dependency
└── Infrastructure/    ← Framework adapters (Doctrine, API Platform, Symfony)
```

### Dependency rules

```
Infrastructure  →  Application  →  Domain
     ✓                 ✓              ✗ (depends on nothing)
```

| Layer | Can depend on | Must NOT depend on |
|-------|---------------|--------------------|
| **Domain** | Only PHP stdlib + `Symfony\Component\Uid\Uuid` | Application, Infrastructure, Doctrine, ApiPlatform, Symfony\Bundle, Symfony\Component\HttpFoundation, Symfony\Component\HttpKernel, Symfony\Component\DependencyInjection |
| **Application** | Domain, `App\Shared\Domain\Port` | Infrastructure, Doctrine, ApiPlatform, Symfony\Bundle, Symfony\Component\HttpFoundation, Symfony\Component\HttpKernel, Symfony\Component\DependencyInjection |
| **Infrastructure** | Application, Domain, any framework namespace | — |

These rules are enforced by **phparkitect** (`phparkitect.php`).

---

## Vertical slicing

Each bounded context (module) is a vertical slice: `Accommodation`, `Image`, `Booking`, etc.

| Rule | Description |
|------|-------------|
| **Module isolation** | Modules must NOT depend on each other. `App\Accommodation` cannot import from `App\Image`. |
| **Shared kernel** | Cross-cutting code lives in `App\Shared\` (e.g. `UuidGenerator`, `EntityProvider`). |
| **Shared independence** | `App\Shared` must NOT depend on any module. |

When adding a new module, add phparkitect rules in `phparkitect.php` to enforce isolation with existing modules.

---

## Domain layer

The domain layer contains the business truth. It is **pure PHP** — no framework imports.

### Value Object

Value objects live alongside entities in `Domain/Entity/`. They encapsulate a single validated value with immutable semantics. See the `domain-code` skill for full conventions.

```php
final readonly class Price
{
    public function __construct(private ?float $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->value) {
            throw InvalidPriceException::becauseNull();
        }
        if ($this->value <= 0) {
            throw InvalidPriceException::becauseNegativeOrZero($this->value);
        }
    }

    public function priceInCents(): int
    {
        return $this->value * 100;
    }
}
```

**Rules:**
- `final readonly` — immutable
- Constructor accepts nullable raw type as a **private promoted property** to validate missing values
- Validation is in a `private function validate()` instance method
- `$value` is private — access through **domain-specific accessor methods** (e.g. `priceInCents()`, not generic `toFloat()`)
- Throws domain exceptions with static named constructors
- Use value objects in entities instead of raw scalars when the field has validation rules

### Entity

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Domain\Entity;

use App\Shared\Domain\Entity\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class <Entity> extends AggregateRoot
{
    public function __construct(
        private readonly Uuid $id,
        private readonly string $name,
        // ... typed fields
    ) {
        // Constructor guards — throw domain exceptions on invalid state
    }

    public function getId(): Uuid { return $this->id; }

    // Domain mutation methods record events
    public function publish(): void
    {
        $this->status = Status::Published;
        $this->recordEvent(new <Entity>Published($this->id));
    }
}
```

**Rules:**
- `final` — not extendable. Use `readonly` on individual immutable properties.
- Entities with mutation methods extend the `AggregateRoot` abstract class to record domain events
- Mutation methods call `$this->recordEvent(new <Event>(...))` after state change
- Validation logic lives in the **constructor** (guard clauses)
- Throws domain exceptions on invariant violations
- Dependencies: `Symfony\Component\Uid\Uuid`, `App\Shared\Domain\Entity\AggregateRoot`

### Domain Event

Domain events live in `src/<Module>/Domain/Event/` and implement `App\Shared\Domain\Event\DomainEvent`.

```php
final readonly class <Entity><Action> implements DomainEvent
{
    public function __construct(public Uuid $<entity>Id) {}
}
```

### AggregateRoot abstract class

`App\Shared\Domain\Entity\AggregateRoot` provides:
- `recordEvent(DomainEvent $event): void` — called by entity mutation methods
- `releaseEvents(): DomainEvent[]` — called by use cases after save to get pending events

### Exception

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Domain\Exception;

final class Invalid<Entity>Exception extends \DomainException
{
    public static function becauseEmptyName(): self
    {
        return new self('Name is required.');
    }

    public static function becauseNegativePrice(float $price): self
    {
        return new self(\sprintf('Price must be positive, got %s.', $price));
    }
}
```

**Rules:**
- Extends `\DomainException` (maps to HTTP 422 via `api_platform.yaml` config)
- Static named constructors: `because<Reason>(<context>): self`
- Descriptive error messages for API consumers
- No exception codes — the message is the contract

### Command

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Domain\Command;

final readonly class <Action><Entity>Command
{
    public function __construct(
        public string $name,
        public ?float $price,
        // ... public promoted properties
    ) {
    }
}
```

**Rules:**
- `final readonly` with public promoted properties
- Pure data carrier — no logic, no validation
- Nullable types for optional fields (validation happens in use case or entity)
- Named arguments when constructing: `new CreateFooCommand(name: 'bar', price: 10.0)`

### Port (interface)

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Domain\Port;

use App\<Module>\Domain\Entity\<Entity>;
use Symfony\Component\Uid\Uuid;

interface <Entity>Repository
{
    public function save(<Entity> $entity): void;
    public function findById(Uuid $id): ?<Entity>;
}
```

**Shared ports** live in `App\Shared\Domain\Port\`:
- `UuidGenerator` — deterministic UUID generation
- `EventBus` — dispatches domain events (`dispatch(array $events): void`)

**Rules:**
- Defined in Domain — implemented in Infrastructure
- Method signatures use **domain types** (domain entities, Uuid), never Doctrine types
- One port per external dependency concept (repository, storage, mailer, etc.)
- Bound to implementations via `config/services.yaml` aliases

---

## Application layer

The application layer orchestrates domain objects. It is **framework-agnostic**.

### Use case

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Application\UseCase;

use App\<Module>\Domain\Command\<Action><Entity>Command;
use App\<Module>\Domain\Entity\<Entity>;
use App\<Module>\Domain\Port\<Entity>Repository;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class <Action><Entity>
{
    public function __construct(
        private <Entity>Repository $repository,
        private EventBus $eventBus,
        // ... other ports
    ) {
    }

    public function handle(<Action><Entity>Command $command): void
    {
        $entity = new <Entity>(
            id: UuidGenerator::generate(),
            name: $command->name,
            // ...
        );

        $this->repository->save($entity);

        $this->eventBus->dispatch($entity->releaseEvents());
    }
}
```

**Rules:**
- `final readonly` — single responsibility
- Single public method: `handle(<Command> $command): void`
- Injects **ports** (interfaces), never concrete implementations
- Uses `UuidGenerator::generate()` from `App\Shared\Domain\Port\UuidGenerator`
- Injects `EventBus` from `App\Shared\Domain\Port\EventBus` to dispatch domain events after save
- After `$this->repository->save($entity)`, call `$this->eventBus->dispatch($entity->releaseEvents())`
- No return value — command pattern (fire and forget)
- Can throw domain exceptions (from entity constructor or explicit checks)

---

## Infrastructure layer

The infrastructure layer adapts external frameworks to domain ports.

### Doctrine entity (ORM mapping)

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Infrastructure\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: Doctrine<Entity>Repository::class)]
#[ORM\Table(name: '<entity>')]
class <Entity>Entity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Getters + fluent setters
    public function getId(): ?Uuid { return $this->id; }
    public function setId(Uuid $id): static { $this->id = $id; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
}
```

**Rules:**
- NOT `final readonly` — Doctrine needs proxy generation and mutability
- UUID primary key — ID is set by the repository via `setId()`, not generated by Doctrine
- Attribute-based mapping (`#[ORM\...]`)
- Fluent setters: return `static` (for chaining)
- This is a **persistence model**, separate from the domain entity

### Doctrine repository (port implementation)

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Infrastructure\Doctrine;

use App\<Module>\Domain\Entity\<Entity> as Domain<Entity>;
use App\<Module>\Domain\Port\<Entity>Repository as <Entity>RepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<<Entity>Entity>
 */
class Doctrine<Entity>Repository extends ServiceEntityRepository implements <Entity>RepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, <Entity>Entity::class);
    }

    public function save(Domain<Entity> $entity): void
    {
        $doctrineEntity = new <Entity>Entity();
        $doctrineEntity->setId($entity->getId());
        $doctrineEntity->setName($entity->getName());

        $em = $this->getEntityManager();
        $em->persist($doctrineEntity);
        $em->flush();
    }

    public function findById(Uuid $id): ?Domain<Entity>
    {
        $entity = $this->find($id);
        return $entity ? $this->toDomain($entity) : null;
    }

    private function toDomain(<Entity>Entity $entity): Domain<Entity>
    {
        return new Domain<Entity>(
            id: $entity->getId(),
            name: $entity->getName(),
        );
    }
}
```

**Rules:**
- Extends `ServiceEntityRepository`, implements domain port
- Converts between Doctrine entity and domain entity (**adapter pattern**)
- `toDomain()` private method for Doctrine → Domain conversion
- Use first-class callable `$this->toDomain(...)` for `array_map`
- `@extends` PHPDoc for generic type

### Messenger (EventBus implementation)

`App\Shared\Infrastructure\Messenger\MessengerEventBus` implements `EventBus` via Symfony Messenger's `MessageBusInterface`.

```php
final readonly class MessengerEventBus implements EventBus
{
    public function __construct(private MessageBusInterface $messageBus) {}

    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->messageBus->dispatch($event);
        }
    }
}
```

Configuration in `config/packages/messenger.yaml` routes `DomainEvent` to the async transport. In test environment, the async transport uses `in-memory://`.

### API Platform (see `api-processor`, `e2e-test`, `openapi-doc` skills for full conventions)

API Platform files live in `src/<Module>/Infrastructure/ApiPlatform/`:

| File | Role |
|------|------|
| `<Entity>Output.php` | `#[ApiResource]` — defines operations, serialization groups, OpenAPI metadata. Implements `FromEntityInterface`. |
| `<Action><Entity>Input.php` | Input DTO for write operations. `final readonly` with `#[Groups]` and `#[ApiProperty]`. |
| `<Action><Entity>Processor.php` | Bridges Input → Command → UseCase. `final readonly`, implements `ProcessorInterface`. |

Shared infrastructure in `src/Shared/ApiPlatform/State/`:
- `EntityProvider` — generic provider converting Doctrine entities to Output DTOs
- `FromEntityInterface` — marker interface with `fromEntity(object $entity): static`

---

## Configuration

### `config/packages/doctrine.yaml` — Module mapping

Each module registers its Doctrine entities:

```yaml
doctrine:
    orm:
        mappings:
            <Module>:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/<Module>/Infrastructure/Doctrine'
                prefix: 'App\<Module>\Infrastructure\Doctrine'
                alias: <Module>
```

### `config/services.yaml` — Port aliases

Domain ports are aliased to infrastructure implementations:

```yaml
App\<Module>\Domain\Port\<Entity>Repository:
    alias: App\<Module>\Infrastructure\Doctrine\Doctrine<Entity>Repository

App\Shared\Domain\Port\EventBus:
    alias: App\Shared\Infrastructure\Messenger\MessengerEventBus
```

### `config/packages/api_platform.yaml` — Exception mapping

```yaml
api_platform:
    exception_to_status:
        DomainException: 422
```

All `\DomainException` subclasses automatically return HTTP 422.

---

## Testing

### Unit tests (Domain + Application)

Located in `tests/Unit/<Module>/`, mirroring source structure.

```
tests/Unit/<Module>/
├── Application/
│   └── UseCase/
│       └── <Action><Entity>Test.php    # Tests the use case
└── Infrastructure/
    └── InMemory<Entity>Repository.php  # In-memory port implementation
```

**InMemory implementations** live in `tests/Unit/<Module>/Infrastructure/` and implement domain ports with simple arrays.

**Test conventions:**
- `#[Before]` / `#[After]` attributes (not `setUp()` / `tearDown()`)
- `#[DataProvider]` with `yield` (`\Generator`) for parameterized tests
- Method names: `test_should_<verb>_<description>` (snake_case)
- Assertions: `self::assert*`
- `UuidGenerator::freeze($id)` / `UuidGenerator::reset()` for deterministic UUIDs

### Integration tests (Doctrine repositories)

Located in `tests/Integration/<Module>/Infrastructure/`.

```
tests/Integration/
├── RepositoryTestCase.php                              # Abstract base (KernelTestCase)
└── <Module>/
    └── Infrastructure/
        └── Doctrine<Entity>RepositoryTest.php
```

- Extends `RepositoryTestCase` which extends `KernelTestCase` (boots kernel, no HTTP client)
- `$alwaysBootKernel = true`
- Gets repository from container via domain port FQCN: `self::getContainer()->get(<Entity>Repository::class)`
- Tests via domain port interface, creates domain entities via constructor
- DB isolation via `dama/doctrine-test-bundle` (auto transaction rollback)
- Must cover: save + findById round-trip, null when not found, update existing entity

### E2E tests (full HTTP flow)

Located in `tests/E2e/<Module>/`.

```
tests/E2e/<Module>/
├── <Module>ApiTestCase.php          # Abstract base with fixture helpers
├── Create<Entity>Test.php           # POST
├── Get<Entity>Test.php              # GET item
├── Get<Entity>CollectionTest.php    # GET collection
└── Delete<Entity>Test.php           # DELETE
```

- Extends `ApiPlatform\Symfony\Bundle\Test\ApiTestCase`
- `$alwaysBootKernel = true`
- Uses `self::createClient()->request(...)` with `application/ld+json`
- DB isolation via `dama/doctrine-test-bundle` (auto transaction rollback)

---

## phparkitect rules

When adding a new module `<Module>`, add these rules in `phparkitect.php`:

```php
// Domain must not depend on Application or Infrastructure
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\<Module>\Domain'))
    ->should(new NotDependsOnTheseNamespaces([
        'App\<Module>\Application',
        'App\<Module>\Infrastructure',
    ]))
    ->because('the domain layer must not depend on application or infrastructure');

// Application must not depend on Infrastructure
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\<Module>\Application'))
    ->should(new NotDependsOnTheseNamespaces(['App\<Module>\Infrastructure']))
    ->because('the application layer must not depend on infrastructure');

// Framework-agnostic rules for Domain and Application
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\<Module>\Domain', 'App\<Module>\Application'))
    ->should(new NotDependsOnTheseNamespaces([
        'Doctrine', 'ApiPlatform',
        'Symfony\Bundle', 'Symfony\Component\HttpFoundation',
        'Symfony\Component\HttpKernel', 'Symfony\Component\DependencyInjection',
    ]))
    ->because('domain and application layers must be framework-agnostic');

// Module isolation (add one per existing module pair)
$rules[] = Rule::allClasses()
    ->that(new ResideInOneOfTheseNamespaces('App\<Module>'))
    ->should(new NotDependsOnTheseNamespaces(['App\<OtherModule>']))
    ->because('modules must not depend on each other');
```

---

## Verification checklist

Every code change must pass these 3 checks:

```bash
docker exec -T api-php bin/phpunit                        # Tests
docker exec -T api-php vendor/bin/php-cs-fixer fix        # Code style
docker exec -T api-php vendor/bin/phparkitect check       # Architecture rules
```
