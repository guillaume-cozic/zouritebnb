<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\PublishAccommodationCommand;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Accommodation\Domain\Port\GalleryRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class PublishAccommodation
{
    public function __construct(
        private AccommodationRepository $repository,
        private GalleryRepository $galleryRepository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(PublishAccommodationCommand $command): void
    {
        $accommodation = $this->repository->findById($command->id);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->id->toRfc4122());
        }

        $gallery = $this->galleryRepository->findByAccommodationId($command->id);

        $accommodation->publish($gallery->count());
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
