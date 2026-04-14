<?php

declare(strict_types=1);

namespace App\User\Domain\Command;

final readonly class AuthenticateUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
