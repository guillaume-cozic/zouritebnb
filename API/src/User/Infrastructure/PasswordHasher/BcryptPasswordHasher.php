<?php

declare(strict_types=1);

namespace App\User\Infrastructure\PasswordHasher;

use App\User\Domain\Port\PasswordHasher;

final readonly class BcryptPasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return password_hash($plain, \PASSWORD_BCRYPT);
    }

    public function verify(string $plain, string $hashed): bool
    {
        return password_verify($plain, $hashed);
    }
}
