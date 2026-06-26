<?php

declare(strict_types=1);

namespace App\User\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class RequestEmailVerificationCommand
{
    public function __construct(public Uuid $userId)
    {
    }
}
