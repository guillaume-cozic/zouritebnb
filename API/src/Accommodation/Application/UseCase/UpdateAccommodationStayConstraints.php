<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\UpdateAccommodationStayConstraintsCommand;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class UpdateAccommodationStayConstraints
{
    public function __construct(
        private AccommodationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UpdateAccommodationStayConstraintsCommand $command): void
    {
        $accommodation = $this->repository->findById($command->accommodationId);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->accommodationId->toRfc4122());
        }

        $accommodation->updateStayConstraints($command->minNights, $command->maxNights);
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
