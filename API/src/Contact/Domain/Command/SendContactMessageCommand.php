<?php

declare(strict_types=1);

namespace App\Contact\Domain\Command;

final readonly class SendContactMessageCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $subject,
        public string $message,
    ) {
    }
}
