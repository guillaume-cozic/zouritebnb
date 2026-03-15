<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

final readonly class Geolocation
{
    public function __construct(
        private float $latitude,
        private float $longitude,
    ) {
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }
}
