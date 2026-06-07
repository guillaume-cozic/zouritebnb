<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Shared\Domain\Port\TeamMembershipChecker;
use App\Shared\Infrastructure\Security\CurrentUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<ConversationOutput>
 */
final readonly class ConversationItemProvider implements ProviderInterface
{
    public function __construct(
        private ConversationRepository $repository,
        private CurrentUser $currentUser,
        private TeamMembershipChecker $teamMembershipChecker,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ConversationOutput
    {
        $id = (string) $uriVariables['id'];
        $conversation = $this->repository->ofId(new ConversationId(Uuid::fromString($id)));

        if (null === $conversation) {
            return null;
        }

        $this->assertParticipant($conversation);

        return ConversationOutput::fromEntity($conversation);
    }

    /**
     * Access to a conversation is restricted to its participants: the guest it concerns,
     * or a member of the host team. Anyone else gets a 403.
     */
    private function assertParticipant(Conversation $conversation): void
    {
        $currentUserId = $this->currentUser->id();

        if ($conversation->isGuest($currentUserId)) {
            return;
        }

        if ($this->teamMembershipChecker->isMember($currentUserId, $conversation->getTeamId())) {
            return;
        }

        throw new AccessDeniedHttpException('You are not a participant of this conversation.');
    }
}
