<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Infrastructure;

use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Port\ConversationRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryConversationRepository implements ConversationRepository
{
    /** @var Conversation[] */
    private array $items = [];

    public function save(Conversation $conversation): void
    {
        $this->items[$conversation->getId()->toString()] = $conversation;
    }

    public function ofId(ConversationId $id): ?Conversation
    {
        return $this->items[$id->toString()] ?? null;
    }

    public function ofReservationId(Uuid $reservationId): ?Conversation
    {
        foreach ($this->items as $conversation) {
            if ($conversation->getReservationId()->equals($reservationId)) {
                return $conversation;
            }
        }

        return null;
    }

    public function listForGuestUser(Uuid $userId): array
    {
        $result = [];
        foreach ($this->items as $conversation) {
            if ($conversation->getGuestUserId()->equals($userId)) {
                $result[] = $conversation;
            }
        }

        return $result;
    }

    public function listForTeam(Uuid $teamId): array
    {
        $result = [];
        foreach ($this->items as $conversation) {
            if ($conversation->getTeamId()->equals($teamId)) {
                $result[] = $conversation;
            }
        }

        return $result;
    }
}
