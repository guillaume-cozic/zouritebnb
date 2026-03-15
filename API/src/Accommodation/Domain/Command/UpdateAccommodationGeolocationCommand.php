<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationGeolocationCommand
{
    public function __construct(
        public Uuid $id,
        public float $latitude,
        public float $longitude,
    ) {
    }
}
