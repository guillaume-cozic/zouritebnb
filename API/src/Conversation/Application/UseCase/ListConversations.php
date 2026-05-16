<?php

declare(strict_types=1);

namespace App\Conversation\Application\UseCase;

use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Shared\Domain\Port\UserTeamProvider;
use Symfony\Component\Uid\Uuid;

final readonly class ListConversations
{
    public function __construct(
        private ConversationRepository $repository,
        private UserTeamProvider $userTeamProvider,
    ) {
    }

    /**
     * Returns conversations where the user is either the guest or a member of the host team.
     *
     * @return Conversation[]
     */
    public function forUser(Uuid $userId): array
    {
        $guestConversations = $this->repository->listForGuestUser($userId);

        $teamId = $this->userTeamProvider->teamIdOf($userId);
        $teamConversations = null !== $teamId ? $this->repository->listForTeam($teamId) : [];

        // Deduplicate by id (user could be both guest and team member, unlikely but handled).
        $byId = [];
        foreach ([...$guestConversations, ...$teamConversations] as $conversation) {
            $byId[$conversation->getId()->toString()] = $conversation;
        }

        $result = array_values($byId);
        usort($result, static fn (Conversation $a, Conversation $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $result;
    }

    /**
     * @return Conversation[]
     */
    public function forTeam(Uuid $teamId): array
    {
        return $this->repository->listForTeam($teamId);
    }
}
