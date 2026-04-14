<?php

declare(strict_types=1);

namespace App\Team\Application\UseCase;

use App\Shared\Domain\Port\EventBus;
use App\Team\Domain\Command\UpdateTeamFavoriteSolidarityProjectCommand;
use App\Team\Domain\Exception\TeamNotFoundException;
use App\Team\Domain\Port\TeamRepository;

final readonly class UpdateTeamFavoriteSolidarityProject
{
    public function __construct(
        private TeamRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UpdateTeamFavoriteSolidarityProjectCommand $command): void
    {
        $team = $this->repository->findById($command->teamId);

        if (null === $team) {
            throw TeamNotFoundException::becauseNotFound($command->teamId->toRfc4122());
        }

        $team->updateFavoriteSolidarityProject($command->favoriteSolidarityProjectId);
        $this->repository->save($team);

        $this->eventBus->dispatch($team->releaseEvents());
    }
}
