<?php

declare(strict_types=1);

namespace App\Notification\Domain\Command;

final readonly class QueueEmailCommand
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        public ?string $recipientEmail,
        public ?string $recipientName,
        public string $subject,
        public string $template,
        public array $variables,
    ) {
    }
}
