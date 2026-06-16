<?php

declare(strict_types=1);

namespace App\Notification\Application\Email;

final readonly class RenderedEmail
{
    public function __construct(
        public string $subject,
        public string $htmlBody,
    ) {
    }
}
