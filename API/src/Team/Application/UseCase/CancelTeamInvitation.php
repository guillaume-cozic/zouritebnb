<?php

declare(strict_types=1);

namespace App\Team\Application\UseCase;

use App\Shared\Domain\Port\EventBus;
use App\Team\Domain\Command\CancelTeamInvitationCommand;
use App\Team\Domain\Event\CoHostInvitationCancelled;
use App\Team\Domain\Exception\InvalidInvitationException;
use App\Team\Domain\Port\TeamInvitationRepository;

final readonly class CancelTeamInvitation
{
    public function __construct(
        private TeamInvitationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(CancelTeamInvitationCommand $command): void
    {
        $invitation = $this->repository->findById($command->invitationId);

        if (null === $invitation) {
            throw InvalidInvitationException::becauseNotFound($command->invitationId->toRfc4122());
        }

        $invitation->cancel();

        $this->repository->save($invitation);

        $this->eventBus->dispatch([new CoHostInvitationCancelled(
            invitationId: $invitation->getId(),
        )]);
    }
}
