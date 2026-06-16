<?php

declare(strict_types=1);

namespace App\Notification\Domain\Command;

final readonly class QueueSmsCommand
{
    public function __construct(
        public ?string $recipientPhone,
        public string $text,
    ) {
    }
}
