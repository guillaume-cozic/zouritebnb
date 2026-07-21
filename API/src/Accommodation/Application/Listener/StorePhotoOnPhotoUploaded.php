<?php

declare(strict_types=1);

namespace App\Accommodation\Application\Listener;

use App\Accommodation\Domain\Entity\Photo;
use App\Accommodation\Domain\Event\AccommodationPhotoUploaded;
use App\Accommodation\Domain\Port\ImageTransformer;
use App\Accommodation\Domain\Port\PhotoRepository;
use App\Accommodation\Domain\Port\PhotoStorage;

final readonly class StorePhotoOnPhotoUploaded
{
    public function __construct(
        private ImageTransformer $imageTransformer,
        private PhotoRepository $photoRepository,
        private PhotoStorage $photoStorage,
    ) {
    }

    public function __invoke(AccommodationPhotoUploaded $event): void
    {
        $transformed = $this->imageTransformer->transform($event->content, $event->mimeType);
        $thumbnail = $this->imageTransformer->thumbnail($event->content, $event->mimeType);

        $filename = \sprintf('%s.webp', $event->photoId->toRfc4122());

        $photo = new Photo(
            id: $event->photoId,
            accommodationId: $event->accommodationId,
            filename: $filename,
            originalName: $event->originalName,
            mimeType: $transformed->mimeType(),
            size: $transformed->size(),
        );

        $this->photoStorage->store($filename, $transformed->content());
        $this->photoStorage->store(Photo::thumbnailFilename($filename), $thumbnail->content());
        $this->photoRepository->save($photo);
    }
}
