<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\UpdateAccommodationAmenitiesCommand;
use App\Accommodation\Domain\Entity\Amenities;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class UpdateAccommodationAmenities
{
    public function __construct(
        private AccommodationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UpdateAccommodationAmenitiesCommand $command): void
    {
        $accommodation = $this->repository->findById($command->id);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->id->toRfc4122());
        }

        $amenities = new Amenities(
            codes: $command->codes,
        );

        $accommodation->updateAmenities($amenities);
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
