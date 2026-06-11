<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\ApiPlatform\State\EntityProvider;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * Provides a single team, restricted to members of that team.
 *
 * @implements ProviderInterface<FromEntityInterface>
 */
final readonly class TeamItemProvider implements ProviderInterface
{
    public function __construct(
        private EntityProvider $entityProvider,
        private CurrentUser $currentUser,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?FromEntityInterface
    {
        $teamId = Uuid::fromString($uriVariables['id']);

        if (!$teamId->equals($this->currentUser->teamId())) {
            throw new AccessDeniedHttpException('You can only access your own team.');
        }

        $output = $this->entityProvider->provide($operation, $uriVariables, $context);

        if (null !== $output && !$output instanceof FromEntityInterface) {
            throw new \LogicException(\sprintf('Expected "%s" or null from the entity provider, got "%s".', FromEntityInterface::class, get_debug_type($output)));
        }

        return $output;
    }
}
