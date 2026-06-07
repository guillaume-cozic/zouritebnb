<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Conversation\Application\UseCase\ListConversations;
use App\Shared\Infrastructure\Security\CurrentUser;

/**
 * @implements ProviderInterface<ConversationOutput>
 */
final readonly class ConversationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ListConversations $listConversations,
        private CurrentUser $currentUser,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        // The identity is always derived from the authenticated user, never from a
        // client-supplied userId/teamId. The use case returns every conversation where
        // the user is either the guest or a member of the host team.
        $conversations = $this->listConversations->forUser($this->currentUser->id());

        return array_map(static fn ($c) => ConversationOutput::fromEntity($c), $conversations);
    }
}
