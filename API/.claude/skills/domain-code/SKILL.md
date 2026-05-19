---
name: domain-code
description: Write domain layer code (value objects, entities, exceptions) following project conventions
argument-hint: <description of what to create, e.g. "Price value object for Accommodation">
allowed-tools: Read, Glob, Grep, Write, Edit, Bash(docker exec*)
---

# Domain Code Writer

Write domain layer code (value objects, entities, exceptions) following the project's hexagonal architecture conventions.

## Input

`$ARGUMENTS` — a description of the domain code to create or refactor (e.g. "Price value object for Accommodation", "add email validation to User entity").

## Steps

1. **Identify the module** (e.g. `Accommodation`, `Booking`) from the arguments.
2. **Read existing domain code** in `src/<Module>/Domain/` to understand current entities, exceptions, and value objects.
3. **Write or update the domain classes** following the conventions below.
4. **Update all consumers** (entity, use case, repository, infrastructure) to use the new domain type.
5. **Update tests** to reflect the changes.
6. **Run checks**:
   - `docker exec -T api-php bin/phpunit --filter 'Unit'` — unit tests
   - `docker exec -T api-php vendor/bin/phparkitect check` — architecture rules

## Conventions

### Value Object

Value objects live in `src/<Module>/Domain/Entity/` alongside entities.

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Domain\Entity;

use App\<Module>\Domain\Exception\Invalid<Name>Exception;

final readonly class <Name>
{
    public function __construct(private ?<scalar> $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->value) {
            throw Invalid<Name>Exception::becauseNull();
        }

        // Additional guards as needed
        if ($this->value <= 0) {
            throw Invalid<Name>Exception::becauseNegativeOrZero($this->value);
        }
    }

    // Domain-specific accessor (e.g. priceInCents(), toString(), toKilometers())
    public function <domainAccessor>(): <returnType>
    {
        return $this->value;
    }
}
```

**Rules:**
- `final readonly` — immutable, not extendable
- Constructor accepts the **nullable** raw type (`?float`, `?string`) as a **private promoted property** so it can validate missing values
- Validation logic is in a `private function validate()` instance method, called from the constructor
- `$value` is private — access is through **domain-specific accessor methods** (e.g. `priceInCents()`, not generic `toFloat()`)
- Throws domain exceptions with static named constructors
- No framework dependencies — pure PHP

### Entity

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Domain\Entity;

use App\<Module>\Domain\Event\<Entity>Published;
use App\Shared\Domain\Entity\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class <Entity> extends AggregateRoot
{
    public function __construct(
        private readonly Uuid $id,
        private readonly <ValueObject> $field,
        // ... readonly for immutable fields
        private <MutableType> $mutableField,
        // ... no readonly for fields that change via domain methods
    ) {
        // Constructor guards for simple invariants not covered by value objects
    }

    // Getters
    public function getId(): Uuid { return $this->id; }
    public function getField(): <ValueObject> { return $this->field; }

    // Domain mutation methods — named after the business action, not the field
    public function publish(): void
    {
        $this->status = Status::Published;
        $this->recordEvent(new <Entity>Published($this->id));
    }

    public function unpublish(): void
    {
        $this->status = Status::Draft;
        $this->recordEvent(new <Entity>Unpublished($this->id));
    }
}
```

**Rules:**
- `final` — not extendable
- Use `readonly` on individual properties that are immutable (id, title, etc.)
- Mutable properties (e.g. status) omit `readonly` to allow domain mutation methods
- **Domain mutation methods** are named after the business action (`publish()`, `cancel()`, `activate()`), never generic (`withStatus()`, `setField()`)
- Mutation methods change state **in place** (`void` return), they do NOT return a new instance
- Mutation methods call `$this->recordEvent(new <Event>(...))` to record domain events
- **One event per method** — a mutation or factory method records **at most one** domain event. If a business action seems to imply two events, fuse them into a single, semantically-richer event (see "Single event per method" below)
- No setters — state changes only through domain methods with business meaning
- Use **value objects** for fields with validation rules (Price, Email, etc.)
- Use **scalar types** for simple fields without invariants (title, description)
- Constructor guards only for invariants not handled by value objects
- Extend `AggregateRoot` abstract class for entities that emit domain events
- Only dependency: `Symfony\Component\Uid\Uuid`, `App\Shared\Domain\Entity\AggregateRoot`, domain events

### Domain Event

Domain events live in `src/<Module>/Domain/Event/`. They are recorded by entities via the `AggregateRoot` base class and dispatched by use cases via `EventBus`.

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class <Entity><Action> implements DomainEvent
{
    public function __construct(public Uuid $<entity>Id)
    {
    }
}
```

**Rules:**
- `final readonly` — immutable event
- Implements `App\Shared\Domain\Event\DomainEvent` marker interface
- Public promoted properties for event data (entity ID, relevant values)
- Named after the business fact that occurred: `AccommodationPublished`, `BookingCancelled`

### Single event per method

A mutation method, factory method, **and** a use case `handle()` records/dispatches **at most one** domain event. Two events in the same method is a smell that the model is leaking implementation steps instead of describing a business fact.

**Fusion rules:**

1. **Two events on the same aggregate mutation → fuse into one richer event.**
   Bad: `Reservation::create()` records `ReservationCreated` + `ReservationConfirmed`.
   Good: `Reservation::create()` records only `ReservationConfirmed` (the meaningful fact). If the "creation" carried info needed by listeners, move those fields into `ReservationConfirmed`.

2. **Two aggregate methods called back-to-back in a use case → fuse into a single aggregate method.**
   Bad: `StartConversation::handle()` calls `Conversation::start()` (records `ConversationStarted`) then `Conversation::postOpeningMessage()` (records `MessagePosted`).
   Good: `Conversation::start(..., MessageId, MessageBody)` posts the opening message internally and records a single `ConversationStarted` enriched with `openingMessageId`.

**Why:**
- Multiple events per state change leak implementation detail to listeners.
- Listeners must deduplicate or coordinate which event to react to.
- The event log becomes harder to read — one business fact should produce one record.
- Forces the modeler to name the actual business event (`ReservationConfirmed`) rather than dumping low-level steps (`Created` + `Confirmed`).

**When NOT to fuse:**
- Two genuinely independent business actions chained for convenience in a use case — but in that case, the second action belongs in a **listener** reacting to the first event, not in the same `handle()`. Example: `RefuseReservation::handle()` records `ReservationRefused`; a listener on `ReservationRefused` then triggers `PostSystemMessage` (which records `MessagePosted` in its own use case).

### Exception

```php
<?php

declare(strict_types=1);

namespace App\<Module>\Domain\Exception;

final class Invalid<Name>Exception extends \DomainException
{
    public static function becauseNull(): self
    {
        return new self('<Name> is required.');
    }

    public static function becauseNegativeOrZero(float $value): self
    {
        return new self(\sprintf('<Name> must be strictly positive, got %s.', $value));
    }

    public static function becauseEmpty(): self
    {
        return new self('<Name> must not be empty.');
    }
}
```

**Rules:**
- Extends `\DomainException` (maps to HTTP 422 via `api_platform.yaml` config)
- Static named constructors: `because<Reason>(<context>): self`
- Descriptive error messages for API consumers
- No exception codes — the message is the contract
- One exception class per validated concept (e.g. `InvalidPriceException`, `InvalidEmailException`)

## Integration checklist

When introducing a value object to replace a raw scalar:

1. **Create** the value object class in `Domain/Entity/`
2. **Create or update** the domain exception in `Domain/Exception/`
3. **Update the entity** to accept the value object instead of the raw scalar
4. **Remove validation** from the entity constructor if it's now handled by the value object
5. **Update the use case** — wrap raw command values with `new <ValueObject>($command->field)`
6. **Remove manual validation** from the use case (the value object handles it)
7. **Update Doctrine repository** — `save()` extracts scalar with the domain accessor (e.g. `->priceInCents()`), `toDomain()` wraps scalar with `new <ValueObject>()`
8. **Update tests** — assert via the domain accessor (e.g. `->getPrice()->priceInCents()`) instead of `->getField()`
9. **API Platform** — no changes needed (Input/Output use raw scalars, the processor/provider handle conversion via the use case/repository)