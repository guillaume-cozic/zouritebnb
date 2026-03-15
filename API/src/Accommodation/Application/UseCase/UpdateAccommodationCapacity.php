<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\UpdateAccommodationCapacityCommand;
use App\Accommodation\Domain\Entity\Capacity;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class UpdateAccommodationCapacity
{
    public function __construct(
        private AccommodationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UpdateAccommodationCapacityCommand $command): void
    {
        $accommodation = $this->repository->findById($command->id);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->id->toRfc4122());
        }

        $capacity = new Capacity(
            bedrooms: $command->bedrooms,
            bathrooms: $command->bathrooms,
            maxGuests: $command->maxGuests,
            singleBeds: $command->singleBeds,
            doubleBeds: $command->doubleBeds,
        );

        $accommodation->updateCapacity($capacity);
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
