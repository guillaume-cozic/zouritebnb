<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Command;

final readonly class SendMessageCommand
{
    public function __construct(
        public string $conversationId,
        public string $authorUserId,
        public string $body,
    ) {
    }
}
