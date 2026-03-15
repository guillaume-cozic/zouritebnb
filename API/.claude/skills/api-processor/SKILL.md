---
name: api-processor
description: Expose a use case as an API Platform write operation (POST/PATCH/PUT/DELETE) via a processor
argument-hint: <UseCaseFQCN or file path>
allowed-tools: Read, Glob, Grep, Write, Edit, Bash(docker exec*)
---

# API Platform Processor Generator

Generate the API Platform infrastructure to expose a use case as a write operation (POST, PATCH, PUT, DELETE), following the project architecture.

## Input

The user provides a use case to expose: `$ARGUMENTS`

If the argument is a file path, read it directly. If it's a class name, find the corresponding file under `src/**/Application/UseCase/`.

## Steps

1. **Read the use case** to understand its `handle(Command $command): void` signature.
2. **Read the command** class to know the fields to accept from the API.
3. **Read the domain entity** to understand the field types.
4. **Read the existing Output class** for the slice (e.g. `<Slice>Output.php`) to see which operations are already declared and which groups exist.
5. **Determine the HTTP method** using the verb selection guide below.
6. **Create the Input class** if it doesn't already exist for this operation.
7. **Create the Processor class** that bridges the Input to the use case Command.
8. **Register the operation** on the existing Output class `#[ApiResource]` attribute.
9. **Run architecture checks** with `docker exec -T api-php vendor/bin/phparkitect check`.

## HTTP Verb Selection

Ask yourself these questions in order:

1. Does the operation only read data? → **GET**
2. Does it create a new resource? → **POST**
3. Does it fully replace a resource or a sub-resource collection? → **PUT**
4. Does it partially update a resource? → **PATCH**
5. Does it delete a resource? → **DELETE**
6. Does it trigger a business process/action? → **POST** (custom operation)

### Examples by verb

**GET** – Read only, no side effects
```http
GET /api/properties                  # List all properties
GET /api/properties/{id}             # Get a single property
GET /api/properties?city=Paris       # Filter properties by city
GET /api/properties/{id}/equipments  # List equipments of a property
```

**POST** – Create a new resource
```http
POST /api/properties
Content-Type: application/json

{
  "title": "Beach House",
  "city": "Nice",
  "pricePerNight": 120
}
```

**PATCH** – Partial update of a resource
```http
PATCH /api/properties/{id}
Content-Type: application/merge-patch+json

{
  "pricePerNight": 150
}
# Only updates the price, everything else stays the same
```

**PUT on a sub-resource** – Replace an entire related collection
```http
PUT /api/properties/{id}/equipments

["wifi", "washing-machine", "dishwasher", "air-conditioning"]
# Replaces the full list of equipments for this property
```

**DELETE** – Remove a resource
```http
DELETE /api/properties/{id}          # Delete a property
DELETE /api/bookings/{id}            # Cancel/delete a booking
```

**POST custom operation** – Trigger a business action
```http
POST /api/bookings/{id}/confirm      # Confirm a booking
POST /api/properties/{id}/publish    # Publish a property listing
POST /api/users/{id}/reset-password  # Trigger a password reset
```

### PUT vs PATCH decision guide

| Scenario | Verb | Endpoint | Why |
|---|---|---|---|
| Change the price of a property | PATCH | `/properties/{id}` | Partial update, one field among many |
| Set all equipments of a property | PUT | `/properties/{id}/equipments` | Full replacement of a related collection |
| Update title and description | PATCH | `/properties/{id}` | Partial update, a few fields |
| Set the available dates for a property | PUT | `/properties/{id}/availabilities` | Full replacement of a related collection |
| Change booking status to "paid" | POST | `/bookings/{id}/pay` | Business action, not a simple field update |

### When to use a dedicated sub-resource endpoint

Use `PUT /resource/{id}/sub-resource` when:
- The sub-resource is a core domain concept (not just a minor detail)
- You want a clear, dedicated contract for managing that relation
- You always replace the entire collection at once

Use `PATCH /resource/{id}` when:
- The update is one field among many
- You want to keep things simple with native API Platform behavior
- The relation is not a central concept

## File locations

All generated files go under `src/<Slice>/Infrastructure/ApiPlatform/`:

| File | Role |
|------|------|
| `<Action><Slice>Processor.php` | Bridges API input → domain command → use case |
| `<Action><Slice>Input.php` | DTO with serialization groups for deserialization |
| `<Slice>Output.php` | Existing resource class — add the new operation here |

## Conventions

### Input class

```php
<?php

declare(strict_types=1);

namespace App\<Slice>\Infrastructure\ApiPlatform;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class <Action><Slice>Input
{
    public function __construct(
        #[Groups(['<slice>:write'])]
        public <type> $<field> = <default>,
        // ... one property per command field
    ) {
    }
}
```

- `final readonly` class with promoted constructor properties
- Each property has a `#[Groups]` attribute with the denormalization group
- Default values match what the Command expects (empty string for strings, null for nullable types)
- The Input is a pure infrastructure DTO — no domain logic, no validation

### Processor class

```php
<?php

declare(strict_types=1);

namespace App\<Slice>\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\<Slice>\Application\UseCase\<UseCaseName>;
use App\<Slice>\Domain\Command\<CommandName>;

/**
 * @implements ProcessorInterface<<InputClass>, void>
 */
final readonly class <Action><Slice>Processor implements ProcessorInterface
{
    public function __construct(
        private <UseCaseName> $<useCaseCamelCase>,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof <InputClass>);

        $this-><useCaseCamelCase>->handle(new <CommandName>(
            <field>: $data-><field>,
            // ... map each Input property to the Command named argument
        ));
    }
}
```

- `final readonly`, implements `ProcessorInterface`
- `@implements` PHPDoc with concrete generic types
- Constructor injects only the use case (single dependency)
- `\assert()` for type narrowing of `$data`
- Maps Input properties to Command named arguments 1:1
- For DELETE/PATCH/PUT: the entity `id` comes from `$uriVariables['id']`, not from the input

### Operation registration on Output class

Add the new operation to the existing `#[ApiResource]` `operations` array:

```php
// POST (create a new resource)
new Post(
    uriTemplate: '/<slices>',
    input: <Action><Slice>Input::class,
    processor: <Action><Slice>Processor::class,
    denormalizationContext: ['groups' => ['<slice>:write']],
    status: 201,
),

// PATCH (partial update of a resource)
new Patch(
    uriTemplate: '/<slices>/{id}',
    input: <Action><Slice>Input::class,
    processor: <Action><Slice>Processor::class,
    denormalizationContext: ['groups' => ['<slice>:write']],
),

// PUT (full replacement of a sub-resource collection)
new Put(
    uriTemplate: '/<slices>/{id}/<sub-resources>',
    input: <Action><Slice>Input::class,
    processor: <Action><Slice>Processor::class,
    denormalizationContext: ['groups' => ['<slice>:write']],
),

// DELETE (remove a resource)
new Delete(
    uriTemplate: '/<slices>/{id}',
    processor: <Action><Slice>Processor::class,
    status: 204,
),

// POST custom operation (trigger a business action)
new Post(
    uriTemplate: '/<slices>/{id}/<action>',
    input: <Action><Slice>Input::class,
    processor: <Action><Slice>Processor::class,
    denormalizationContext: ['groups' => ['<slice>:write']],
),
```

### Rules

- `declare(strict_types=1)` in every file
- All classes are `final readonly`
- The Processor never contains business logic — it only maps Input → Command and delegates to the use case
- The Input class field names should match the Command field names
- Use named arguments when constructing the Command
- One Processor per use case (not one per HTTP method)
- Import the correct `ApiPlatform\Metadata\*` class for the operation (Post, Patch, Put, Delete)
- The Output class `shortName` and existing `provider`/`stateOptions` must not be modified