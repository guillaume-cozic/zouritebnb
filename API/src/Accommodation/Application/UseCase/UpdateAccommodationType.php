<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\UpdateAccommodationTypeCommand;
use App\Accommodation\Domain\Entity\AccommodationType;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class UpdateAccommodationType
{
    public function __construct(
        private AccommodationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UpdateAccommodationTypeCommand $command): void
    {
        $accommodation = $this->repository->findById($command->accommodationId);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->accommodationId->toRfc4122());
        }

        $accommodation->updateType(AccommodationType::fromString($command->type));
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
