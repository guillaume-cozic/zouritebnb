<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\UnpublishAccommodationCommand;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class UnpublishAccommodation
{
    public function __construct(
        private AccommodationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UnpublishAccommodationCommand $command): void
    {
        $accommodation = $this->repository->findById($command->id);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->id->toRfc4122());
        }

        $accommodation->unpublish();
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
