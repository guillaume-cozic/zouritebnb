<?php

declare(strict_types=1);

namespace App\User\Domain\Command;

final readonly class VerifyEmailCommand
{
    public function __construct(public string $token)
    {
    }
}
