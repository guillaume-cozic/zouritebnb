<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

final readonly class ConversationMessageView
{
    public function __construct(
        public Uuid $conversationId,
        public Uuid $messageId,
        public Uuid $teamId,
        public Uuid $guestUserId,
        public Uuid $accommodationId,
        public ?Uuid $authorUserId,
        public string $body,
        public bool $isSystem,
    ) {
    }
}
