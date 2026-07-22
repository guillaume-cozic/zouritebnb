<?php

declare(strict_types=1);

namespace App\ActivityPoint\Domain\Entity;

use App\ActivityPoint\Domain\Exception\InvalidActivityPointException;

final readonly class Coordinates
{
    private const float MIN_LATITUDE = -20.05;
    private const float MAX_LATITUDE = -19.35;
    private const float MIN_LONGITUDE = 62.95;
    private const float MAX_LONGITUDE = 63.95;

    public function __construct(
        private ?float $latitude,
        private ?float $longitude,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->latitude) {
            throw InvalidActivityPointException::becauseLatitudeIsMissing();
        }

        if (null === $this->longitude) {
            throw InvalidActivityPointException::becauseLongitudeIsMissing();
        }

        if ($this->latitude < self::MIN_LATITUDE || $this->latitude > self::MAX_LATITUDE) {
            throw InvalidActivityPointException::becauseLatitudeIsOutOfBounds($this->latitude);
        }

        if ($this->longitude < self::MIN_LONGITUDE || $this->longitude > self::MAX_LONGITUDE) {
            throw InvalidActivityPointException::becauseLongitudeIsOutOfBounds($this->longitude);
        }
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
