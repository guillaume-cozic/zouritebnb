<?php

declare(strict_types=1);

namespace App\Conversation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Conversation\Application\UseCase\ListConversations;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<ConversationOutput>
 */
final readonly class ConversationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ListConversations $listConversations,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $userIdParam = $request?->query->get('userId');
        $teamIdParam = $request?->query->get('teamId');

        if (\is_string($teamIdParam) && '' !== $teamIdParam) {
            $conversations = $this->listConversations->forTeam(Uuid::fromString($teamIdParam));
        } elseif (\is_string($userIdParam) && '' !== $userIdParam) {
            $conversations = $this->listConversations->forUser(Uuid::fromString($userIdParam));
        } else {
            return [];
        }

        return array_map(static fn ($c) => ConversationOutput::fromEntity($c), $conversations);
    }
}
