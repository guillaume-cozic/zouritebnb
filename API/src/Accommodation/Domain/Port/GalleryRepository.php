<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Port;

use App\Accommodation\Domain\Entity\Gallery;
use Symfony\Component\Uid\Uuid;

interface GalleryRepository
{
    public function findByAccommodationId(Uuid $accommodationId): Gallery;

    public function save(Gallery $gallery): void;
}
