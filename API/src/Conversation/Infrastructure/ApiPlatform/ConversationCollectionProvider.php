<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Conversation\Application\UseCase\ListConversations;
use App\Shared\Domain\Port\UserContactProvider;
use App\Shared\Infrastructure\Security\CurrentUser;

/**
 * @implements ProviderInterface<ConversationOutput>
 */
final readonly class ConversationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ListConversations $listConversations,
        private CurrentUser $currentUser,
        private UserContactProvider $userContactProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        // The identity is always derived from the authenticated user, never from a
        // client-supplied userId/teamId. The use case returns every conversation where
        // the user is either the guest or a member of the host team.
        $conversations = $this->listConversations->forUser($this->currentUser->id());

        return array_map(function ($c) {
            $output = ConversationOutput::fromEntity($c);
            $output->applyGuestContact($this->userContactProvider->contactOf($c->getGuestUserId()));

            return $output;
        }, $conversations);
    }
}
