<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\DeleteAccommodationPhotoCommand;
use App\Accommodation\Domain\Event\AccommodationPhotoDeleted;
use App\Accommodation\Domain\Exception\PhotoNotFoundException;
use App\Accommodation\Domain\Port\GalleryRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class DeleteAccommodationPhoto
{
    public function __construct(
        private GalleryRepository $galleryRepository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(DeleteAccommodationPhotoCommand $command): void
    {
        $gallery = $this->galleryRepository->findByAccommodationId($command->accommodationId);

        if (!$gallery->hasPhoto($command->photoId)) {
            throw PhotoNotFoundException::becauseNotFound($command->photoId->toRfc4122());
        }

        $gallery->removePhoto($command->photoId);
        $this->galleryRepository->save($gallery);

        $this->eventBus->dispatch([new AccommodationPhotoDeleted(
            accommodationId: $command->accommodationId,
            filename: \sprintf('%s.webp', $command->photoId->toRfc4122()),
            photoId: $command->photoId,
        )]);
    }
}
