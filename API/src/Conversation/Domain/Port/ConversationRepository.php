<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Port;

use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use Symfony\Component\Uid\Uuid;

interface ConversationRepository
{
    public function save(Conversation $conversation): void;

    public function ofId(ConversationId $id): ?Conversation;

    public function ofReservationId(Uuid $reservationId): ?Conversation;

    /**
     * Lists conversations where the user is the guest.
     *
     * @return Conversation[]
     */
    public function listForGuestUser(Uuid $userId): array;

    /**
     * Lists conversations attached to a host team.
     *
     * @return Conversation[]
     */
    public function listForTeam(Uuid $teamId): array;
}
