<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Shared\Domain\Port\ConversationMessageProvider;
use App\Shared\Domain\Port\ConversationMessageView;
use Symfony\Component\Uid\Uuid;

final class InMemoryConversationMessageProvider implements ConversationMessageProvider
{
    /** @var array<string, ConversationMessageView> */
    private array $messages = [];

    public function add(ConversationMessageView $message): void
    {
        $this->messages[$message->messageId->toRfc4122()] = $message;
    }

    public function findMessage(Uuid $conversationId, Uuid $messageId): ?ConversationMessageView
    {
        $message = $this->messages[$messageId->toRfc4122()] ?? null;

        if (null === $message || !$message->conversationId->equals($conversationId)) {
            return null;
        }

        return $message;
    }
}
