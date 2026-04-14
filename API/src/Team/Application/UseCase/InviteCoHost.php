<?php

declare(strict_types=1);

namespace App\Team\Application\UseCase;

use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;
use App\Team\Domain\Command\InviteCoHostCommand;
use App\Team\Domain\Entity\InvitationStatus;
use App\Team\Domain\Entity\TeamInvitation;
use App\Team\Domain\Event\CoHostInvited;
use App\Team\Domain\Exception\InvalidInvitationException;
use App\Team\Domain\Port\TeamInvitationRepository;

final readonly class InviteCoHost
{
    public function __construct(
        private TeamInvitationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(InviteCoHostCommand $command): string
    {
        $normalizedEmail = strtolower(trim($command->email));

        foreach ($this->repository->findPendingByTeam($command->teamId) as $existing) {
            if (strtolower($existing->getEmail()) === $normalizedEmail) {
                throw InvalidInvitationException::becauseAlreadyInvited($command->email);
            }
        }

        $invitation = new TeamInvitation(
            id: UuidGenerator::generate(),
            teamId: $command->teamId,
            email: $command->email,
            status: InvitationStatus::Pending,
            createdAt: new \DateTimeImmutable(),
        );

        $this->repository->save($invitation);

        $this->eventBus->dispatch([new CoHostInvited(
            invitationId: $invitation->getId(),
            teamId: $invitation->getTeamId(),
            email: $invitation->getEmail(),
        )]);

        return $invitation->getId()->toRfc4122();
    }
}
