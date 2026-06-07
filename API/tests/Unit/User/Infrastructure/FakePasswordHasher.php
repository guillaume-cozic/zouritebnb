<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure;

use App\User\Domain\Port\PasswordHasher;

/**
 * Deterministic password hasher for unit tests: hashing prefixes the plain
 * password with "hashed:" and verification reverses that transformation.
 */
final class FakePasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return 'hashed:'.$plain;
    }

    public function verify(string $plain, string $hashed): bool
    {
        return $hashed === 'hashed:'.$plain;
    }
}
