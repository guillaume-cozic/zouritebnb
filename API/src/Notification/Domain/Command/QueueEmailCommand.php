<?php

declare(strict_types=1);

namespace App\Notification\Domain\Command;

final readonly class QueueEmailCommand
{
    public function __construct(
        public ?string $recipientEmail,
        public ?string $recipientName,
        public string $subject,
        public string $htmlBody,
    ) {
    }
}
