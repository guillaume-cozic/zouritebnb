<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Infrastructure;

use App\Accommodation\Domain\Entity\Gallery;
use App\Accommodation\Domain\Port\GalleryRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryGalleryRepository implements GalleryRepository
{
    /** @var Gallery[] */
    private array $galleries = [];

    public function findByAccommodationId(Uuid $accommodationId): Gallery
    {
        return $this->galleries[$accommodationId->toRfc4122()]
            ?? new Gallery(accommodationId: $accommodationId);
    }

    public function save(Gallery $gallery): void
    {
        $this->galleries[$gallery->getAccommodationId()->toRfc4122()] = $gallery;
    }
}
