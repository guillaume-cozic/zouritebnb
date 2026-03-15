<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\PublishAccommodationCommand;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class PublishAccommodation
{
    public function __construct(
        private AccommodationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(PublishAccommodationCommand $command): void
    {
        $accommodation = $this->repository->findById($command->id);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->id->toRfc4122());
        }

        $accommodation->publish();
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
