<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationCapacityCommand
{
    public function __construct(
        public Uuid $id,
        public int $bedrooms,
        public int $bathrooms,
        public int $maxGuests,
        public int $singleBeds,
        public int $doubleBeds,
    ) {
    }
}
