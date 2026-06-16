<?php

declare(strict_types=1);

namespace App\Notification\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class ResendEmailCommand
{
    public function __construct(public Uuid $emailId)
    {
    }
}
