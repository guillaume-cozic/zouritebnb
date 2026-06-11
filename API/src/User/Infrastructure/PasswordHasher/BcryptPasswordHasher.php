<?php

declare(strict_types=1);

namespace App\User\Infrastructure\PasswordHasher;

use App\User\Domain\Port\PasswordHasher;

final readonly class BcryptPasswordHasher implements PasswordHasher
{
    /** Work factor: 12 is the current sensible default, above PHP's default of 10. */
    private const int COST = 12;

    public function hash(string $plain): string
    {
        return password_hash($plain, \PASSWORD_BCRYPT, ['cost' => self::COST]);
    }

    public function verify(string $plain, string $hashed): bool
    {
        return password_verify($plain, $hashed);
    }
}
