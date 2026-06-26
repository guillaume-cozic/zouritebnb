<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure;

use App\User\Domain\Port\TokenGenerator;

/**
 * Deterministic token generator for unit tests: returns a fixed, predictable raw token
 * so tests can assert on the stored hash and rebuild reset/verification links.
 */
final class FakeTokenGenerator implements TokenGenerator
{
    public function __construct(private readonly string $token = 'fixed-raw-token')
    {
    }

    public function generate(): string
    {
        return $this->token;
    }
}
