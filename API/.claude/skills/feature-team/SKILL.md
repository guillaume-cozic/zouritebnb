---
name: feature-team
description: Spawn a team of 3 agents (domain, infrastructure, API Platform) to implement a complete vertical slice
argument-hint: <feature description in natural language>
---

# Feature Team — Vertical Slice Implementation

Spawn a coordinated team of 3 agents to implement a complete vertical slice following the project's hexagonal architecture. Each agent writes its code **and** the corresponding tests.

## Architecture reference

**Before spawning agents**, read the `hexagonal-architecture` skill at `.claude/skills/hexagonal-architecture/SKILL.md`. It contains all architecture conventions, layer rules, code patterns, and phparkitect rules that agents must follow. Pass relevant sections to each agent in their prompt.

## Input

The user describes the feature to implement: `$ARGUMENTS`

If the description is vague or missing critical details, **ask clarifying questions** using `AskUserQuestion` before spawning the team. You need to know at minimum:
- The module name (e.g. `Image`, `Booking`, `Review`)
- The domain entity fields and their types
- The validation rules (constructor guards)
- The API operations to expose (POST, GET, GET collection, PATCH, DELETE)
- Any relationships to existing modules (e.g. "an image belongs to an accommodation")

Once requirements are clear, create the team and orchestrate the 3 agents.

## Team orchestration

Create a team named `feature-<module>` (e.g. `feature-image`), then create 3 tasks with dependencies:

```
Task #1: domain-agent     (no dependencies)
Task #2: infra-agent      (blocked by #1)
Task #3: api-agent         (blocked by #1)
```

Spawn agents sequentially as their blockers complete. Each agent runs with `mode: bypassPermissions`.

When all 3 agents are done, send shutdown requests and clean up the team with `TeamDelete`.

---

## Agent 1: `domain-agent`

**Role:** Implement the pure domain and application layers — entities, exceptions, commands, ports, use cases — plus unit tests. Everything must be **framework-agnostic**.

**Architecture reference:** Follow the **Domain layer**, **Application layer**, and **Testing > Unit tests** sections from `.claude/skills/hexagonal-architecture/SKILL.md`.

### Files to create

| File | Description |
|------|-------------|
| `src/<Module>/Domain/Entity/<Entity>.php` | Final readonly entity with constructor validation guards |
| `src/<Module>/Domain/Exception/Invalid<Entity>Exception.php` | Domain exception with static named constructors (`because*()`) |
| `src/<Module>/Domain/Command/<Action><Entity>Command.php` | Final readonly command DTO with public properties |
| `src/<Module>/Domain/Port/<Entity>Repository.php` | Repository interface (`save`, `findById`, etc.) |
| `src/<Module>/Domain/Port/*` | Any additional port interfaces needed (e.g. `ImageStorage`) |
| `src/<Module>/Application/UseCase/<Action><Entity>.php` | Final readonly use case with single `handle(Command): void` method |
| `tests/Unit/<Module>/Infrastructure/InMemory<Entity>Repository.php` | In-memory repository implementing the port |
| `tests/Unit/<Module>/Infrastructure/InMemory*.php` | In-memory implementations for any additional ports |
| `tests/Unit/<Module>/Application/UseCase/<Action><Entity>Test.php` | Unit tests for the use case |

### Additional instructions

- If `UuidGenerator` doesn't exist in `App\Shared\Domain\Port`, move it there from wherever it is and update all existing imports
- Read an existing module (e.g. `src/Accommodation/`) as reference for patterns

### Verification commands

```bash
docker exec -T api-php bin/phpunit tests/Unit/<Module>/
docker exec -T api-php vendor/bin/php-cs-fixer fix
docker exec -T api-php vendor/bin/phparkitect check
```

---

## Agent 2: `infra-agent`

**Role:** Implement the infrastructure layer — Doctrine entity and repository, file storage or other adapters, Symfony config, and database migration. Must implement the domain port interfaces created by Agent 1.

**Architecture reference:** Follow the **Infrastructure layer** and **Configuration** sections from `.claude/skills/hexagonal-architecture/SKILL.md`.

### Files to create

| File | Description |
|------|-------------|
| `src/<Module>/Infrastructure/Doctrine/<Entity>Entity.php` | Doctrine ORM entity with attribute mapping |
| `src/<Module>/Infrastructure/Doctrine/Doctrine<Entity>Repository.php` | Repository extending `ServiceEntityRepository`, implementing domain port |
| `src/<Module>/Infrastructure/Storage/*` | Additional infrastructure adapters (e.g. `LocalImageStorage`) |
| `migrations/Version*.php` | Auto-generated Doctrine migration |

### Integration tests to create

| File | Description |
|------|-------------|
| `tests/Integration/RepositoryTestCase.php` | Abstract base test case (if not exists) |
| `tests/Integration/<Module>/Infrastructure/Doctrine<Entity>RepositoryTest.php` | Integration tests for the Doctrine repository |

Follow the conventions from `.claude/skills/integration-test/SKILL.md`:
- Get repository from container via domain port FQCN
- Test via domain interface, create domain entities via constructor
- Cover: save + findById round-trip, null when not found, update existing entity
- Add tests for any module-specific repository methods

### Config to update

- `config/packages/doctrine.yaml` — Add module mapping
- `config/services.yaml` — Add port-to-implementation aliases
- `phparkitect.php` — Add architecture rules for the new module

See **Configuration** and **phparkitect rules** sections in the hexagonal-architecture skill for exact format.

### Additional instructions

- Read the domain port interfaces first to understand what to implement
- Read an existing module (e.g. `src/Accommodation/Infrastructure/Doctrine/`) as reference
- Generate and run the Doctrine migration after creating the entity

### Verification commands

```bash
docker exec -T api-php bin/console doctrine:migrations:diff
docker exec -T api-php bin/console doctrine:migrations:migrate --no-interaction
docker exec -T api-php bin/phpunit tests/Integration/<Module>/
docker exec -T api-php bin/phpunit
docker exec -T api-php vendor/bin/php-cs-fixer fix
docker exec -T api-php vendor/bin/phparkitect check
```

---

## Agent 3: `api-agent`

**Role:** Expose the use case(s) as REST API endpoints via API Platform — Input/Output DTOs, Processor, OpenAPI documentation — plus E2E tests covering happy paths and error cases.

**Architecture reference:** Follow the **Infrastructure layer > API Platform** section from `.claude/skills/hexagonal-architecture/SKILL.md`. Also follow the conventions from these skills:
- `.claude/skills/api-processor/SKILL.md` — Processor + Input conventions
- `.claude/skills/e2e-test/SKILL.md` — E2E test conventions
- `.claude/skills/openapi-doc/SKILL.md` — OpenAPI enrichment conventions

### Files to create

| File | Description |
|------|-------------|
| `src/<Module>/Infrastructure/ApiPlatform/<Action><Entity>Input.php` | Input DTO for deserialization |
| `src/<Module>/Infrastructure/ApiPlatform/<Action><Entity>Processor.php` | State processor bridging Input → Command → UseCase |
| `src/<Module>/Infrastructure/ApiPlatform/<Entity>Output.php` | Output DTO with `#[ApiResource]`, operations, and OpenAPI metadata |
| `tests/E2e/<Module>/<Module>ApiTestCase.php` | Abstract base test case with fixture helpers |
| `tests/E2e/<Module>/<Action><Entity>Test.php` | E2E test per route (POST, GET, etc.) |

### Additional instructions

- Read the domain command and use case first to understand the Input → Command mapping
- Read the Doctrine entity to understand the Output → fromEntity mapping
- Read an existing module (e.g. `src/Accommodation/Infrastructure/ApiPlatform/`) as reference
- OpenAPI descriptions must be in **French**
- Include valid + invalid request examples for write operations
- E2E tests: test happy paths AND each domain validation error (422)

### Verification commands

```bash
docker exec -T api-php bin/phpunit
docker exec -T api-php vendor/bin/php-cs-fixer fix
docker exec -T api-php vendor/bin/phparkitect check
```

---

## Global rules

- `declare(strict_types=1)` in **every** PHP file
- All domain/application classes are `final readonly`
- Named arguments for all constructor calls
- Domain and Application layers must be **framework-agnostic** (no Doctrine, Symfony, API Platform imports)
- Modules must not depend on each other (only on `Shared` and own namespace)
- Each agent must run all 3 verification commands before reporting completion