---
name: unit-test
description: Generate unit tests for a use case following project conventions
argument-hint: <UseCaseFQCN or file path>
allowed-tools: Read, Glob, Grep, Write, Edit, Bash(docker exec*)
---

# Unit Test Generator for Use Cases

Generate a PHPUnit unit test for a use case class, following the project conventions.

## Input

The user provides a use case to test: `$ARGUMENTS`

If the argument is a file path, read it directly. If it's a class name, find the corresponding file under `src/**/Application/UseCase/`.

## Steps

1. **Read the use case** class to understand its constructor dependencies and `handle()` method signature.
2. **Read the command** class (the parameter of `handle()`) to understand its properties.
3. **Read the domain entity** and any **domain exceptions** involved to understand validation rules and constructor arguments.
4. **Read the repository port** (interface) used by the use case.
5. **Check if an InMemory implementation** of the repository already exists under `tests/Unit/<Slice>/Infrastructure/`. If not, create one.
6. **Generate the test class** following the conventions below.
7. **Run the tests** with `docker exec -T api-php bin/phpunit <test-file>` to verify they pass.

## Conventions

### File location
Tests go in `tests/Unit/<Slice>/Application/UseCase/<UseCaseName>Test.php`, mirroring the source structure.

### Test class structure
```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\<Slice>\Application\UseCase;

// imports...
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class <UseCaseName>Test extends TestCase
{
    // Declare test doubles and use case as private properties
    private InMemory<Repository> $repository;
    private <UseCaseName> $useCase;

    #[Before]
    public function initUseCase(): void
    {
        // Instantiate InMemory implementations and the use case
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    // Happy path test(s) first
    public function test_should_<expected_behavior>(): void { ... }

    // Validation / error tests grouped with #[DataProvider] when possible
    #[DataProvider('<providerName>')]
    public function test_should_not_<action>_with_<invalid_thing>(...): void { ... }

    public static function <providerName>(): \Generator
    {
        yield 'case label' => [...];
    }

    // Private assertion helpers at the bottom
    private function assert<Entity>Saved(...): void { ... }
}
```

### Rules
- Use `#[Before]` / `#[After]` attributes instead of `setUp()` / `tearDown()`
- Use `#[DataProvider]` with `yield` (Generator) for similar test cases
- Test method names use snake_case: `test_should_<verb>_<description>`
- Use `self::assert*` for assertions
- Use named arguments when constructing commands
- Freeze UUIDs with `UuidGenerator::freeze()` when the test needs to assert on IDs
- Always reset `UuidGenerator` in `#[After]`
- InMemory repositories implement the domain port interface and store entities in an array keyed by UUID
- One test file per use case
- Test both happy paths and error/validation paths
- For domain exceptions, assert both the exception class and the message