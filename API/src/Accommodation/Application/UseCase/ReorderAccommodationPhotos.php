<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\ReorderAccommodationPhotosCommand;
use App\Accommodation\Domain\Port\GalleryRepository;

final readonly class ReorderAccommodationPhotos
{
    public function __construct(
        private GalleryRepository $galleryRepository,
    ) {
    }

    public function handle(ReorderAccommodationPhotosCommand $command): void
    {
        $gallery = $this->galleryRepository->findByAccommodationId($command->accommodationId);
        $gallery->reorder($command->photoIds);
        $this->galleryRepository->save($gallery);
    }
}
