<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class ConversationStarted implements DomainEvent
{
    public function __construct(
        public Uuid $conversationId,
        public Uuid $reservationId,
        public Uuid $openingMessageId,
    ) {
    }
}
