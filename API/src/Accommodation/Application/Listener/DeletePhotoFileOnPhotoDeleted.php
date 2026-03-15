<?php

declare(strict_types=1);

namespace App\Accommodation\Application\Listener;

use App\Accommodation\Domain\Event\AccommodationPhotoDeleted;
use App\Accommodation\Domain\Port\PhotoRepository;
use App\Accommodation\Domain\Port\PhotoStorage;

final readonly class DeletePhotoFileOnPhotoDeleted
{
    public function __construct(
        private PhotoRepository $photoRepository,
        private PhotoStorage $photoStorage,
    ) {
    }

    public function __invoke(AccommodationPhotoDeleted $event): void
    {
        $photo = $this->photoRepository->findById($event->photoId);

        if (null !== $photo) {
            $this->photoRepository->delete($photo);
        }

        $this->photoStorage->delete($event->filename);
    }
}
