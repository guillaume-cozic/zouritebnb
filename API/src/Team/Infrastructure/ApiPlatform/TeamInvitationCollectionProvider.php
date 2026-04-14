<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Team\Domain\Port\TeamInvitationRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<TeamInvitationOutput>
 */
final readonly class TeamInvitationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private TeamInvitationRepository $repository,
    ) {
    }

    /**
     * @return TeamInvitationOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $teamId = Uuid::fromString($uriVariables['id']);
        $invitations = $this->repository->findPendingByTeam($teamId);

        return array_map(
            static fn ($invitation) => TeamInvitationOutput::fromDomain($invitation),
            $invitations,
        );
    }
}
