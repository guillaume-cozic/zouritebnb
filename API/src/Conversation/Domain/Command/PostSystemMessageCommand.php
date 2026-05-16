<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class PostSystemMessageCommand
{
    public function __construct(
        public Uuid $reservationId,
        public string $body,
    ) {
    }
}
