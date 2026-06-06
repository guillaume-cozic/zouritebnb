# CLAUDE.md

## Stack

- **Symfony 8.0** with PHP 8.5
- **Docker**: PHP 8.5-FPM + Nginx + MySQL 8.4
- **Doctrine ORM** for database
- **API Platform** for REST endpoints
- **PHPUnit 12** for tests

## Commands

All commands run inside the PHP container:

```bash
# Start services
docker compose up -d

# Tests
docker exec -T api-php bin/phpunit

# Code style (php-cs-fixer)
docker exec -T api-php vendor/bin/php-cs-fixer fix

# Architecture rules (phparkitect)
docker exec -T api-php vendor/bin/phparkitect check
```

## Architecture

### Vertical slices + Hexagonal

Each module (e.g. `Accommodation`) follows this structure:

```
src/<Module>/
├── Application/
│   └── UseCase/          # Use cases (final readonly, handle() method)
├── Domain/
│   ├── Command/          # Command objects (final readonly)
│   ├── Entity/           # Domain entities (final readonly)
│   ├── Exception/        # Domain exceptions (DomainException)
│   └── Port/             # Interfaces (Repository, UuidGenerator...)
└── Infrastructure/
    └── Doctrine/         # Doctrine implementations
```

### Rules

- **Domain** must not depend on Application or Infrastructure
- **Application** must not depend on Infrastructure
- **Domain and Application** must be framework-agnostic (no Doctrine, Symfony, ApiPlatform imports)
- **Modules** must not depend on each other (only on `Shared` and own namespace)
- Validation logic lives in domain entities (constructor guards)

## Code conventions

- `declare(strict_types=1)` in every PHP file
- All classes are `final readonly` when possible
- Use cases have a single `handle(Command $command): void` method
- Named arguments for command constructors
- Domain exceptions use static named constructors (`becauseNull()`, `becauseNegativeOrZero()`)

## Skills

Skills in `.claude/skills/` define the conventions and patterns for this project. **You MUST consult and follow the relevant skill(s) before writing any code.** Match skills to your task:

| Skill | When to use |
|-------|-------------|
| `hexagonal-architecture` | Any code change — reference for layers, dependencies, file structure |
| `domain-code` | Value objects, entities, exceptions, domain logic |
| `api-processor` | Exposing a use case as a POST/PATCH/PUT/DELETE endpoint |
| `e2e-test` | Writing E2E tests for API routes |
| `integration-test` | Writing integration tests for Doctrine repositories |
| `unit-test` | Writing unit tests for use cases |
| `openapi-doc` | Enriching OpenAPI descriptions, examples, summaries |
| `contract-test` | Adding/maintaining OpenAPI contract tests between API and front |
| `feature-team` | Implementing a full vertical slice (spawns 3 agents) |

**When creating a new feature or module** (e.g. "create the Image module", "add reviews"), **always use the `feature-team` skill**. It orchestrates 3 agents that cover domain, infrastructure (with integration tests), and API layers in parallel.

**At the end of every response, list the skill(s) you used:**

```
Skills used: `domain-code`, `unit-test`
```

If no skill was relevant, write `Skills used: none`.

## Test conventions

- Tests mirror source structure: `tests/Unit/<Module>/Application/UseCase/<Name>Test.php`
- Use `#[Before]` / `#[After]` attributes instead of `setUp()` / `tearDown()`
- Use `#[DataProvider]` with `yield` (Generator) for similar test cases
- Test method names: `test_should_<verb>_<description>` (snake_case)
- InMemory repositories in `tests/Unit/<Module>/Infrastructure/` implement domain ports
- `UuidGenerator::freeze()` / `UuidGenerator::reset()` for deterministic UUIDs
- Assertions use `self::assert*`
