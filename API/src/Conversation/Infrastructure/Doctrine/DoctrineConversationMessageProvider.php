<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\Doctrine;

use App\Shared\Domain\Port\ConversationMessageProvider;
use App\Shared\Domain\Port\ConversationMessageView;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineConversationMessageProvider implements ConversationMessageProvider
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findMessage(Uuid $conversationId, Uuid $messageId): ?ConversationMessageView
    {
        $message = $this->entityManager->find(MessageEntity::class, $messageId);
        $conversation = $message?->getConversation();

        if (null === $message || null === $conversation || (string) $conversation->getId() !== (string) $conversationId) {
            return null;
        }

        return new ConversationMessageView(
            conversationId: $conversationId,
            messageId: $messageId,
            teamId: $conversation->getTeamId(),
            guestUserId: $conversation->getGuestUserId(),
            accommodationId: $conversation->getAccommodationId(),
            authorUserId: $message->getAuthorUserId(),
            body: (string) $message->getBody(),
            isSystem: $message->isSystem(),
        );
    }
}
