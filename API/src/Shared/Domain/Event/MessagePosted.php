<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published by the Conversation context when a message is posted.
 * Consumers in other contexts can react — e.g. Notification emails the recipient.
 */
final readonly class MessagePosted implements DomainEvent
{
    public function __construct(
        public Uuid $conversationId,
        public Uuid $messageId,
        public bool $isSystem,
    ) {
    }
}
