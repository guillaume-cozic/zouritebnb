<?php

declare(strict_types=1);

namespace App\Conversation\Application\UseCase;

use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Exception\ConversationNotFoundException;
use App\Conversation\Domain\Port\ConversationRepository;
use Symfony\Component\Uid\Uuid;

final readonly class GetConversation
{
    public function __construct(private ConversationRepository $repository)
    {
    }

    public function byId(string $id): Conversation
    {
        $conversation = $this->repository->ofId(new ConversationId(Uuid::fromString($id)));
        if (null === $conversation) {
            throw ConversationNotFoundException::becauseId($id);
        }

        return $conversation;
    }
}
