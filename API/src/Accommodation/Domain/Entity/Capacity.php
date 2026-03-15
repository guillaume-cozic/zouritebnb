<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidCapacityException;

final readonly class Capacity
{
    public function __construct(
        private int $bedrooms,
        private int $bathrooms,
        private int $maxGuests,
        private int $singleBeds,
        private int $doubleBeds,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->bedrooms < 0) {
            throw InvalidCapacityException::becauseNegative('bedrooms', $this->bedrooms);
        }

        if ($this->bathrooms < 0) {
            throw InvalidCapacityException::becauseNegative('bathrooms', $this->bathrooms);
        }

        if ($this->maxGuests < 0) {
            throw InvalidCapacityException::becauseNegative('maxGuests', $this->maxGuests);
        }

        if ($this->singleBeds < 0) {
            throw InvalidCapacityException::becauseNegative('singleBeds', $this->singleBeds);
        }

        if ($this->doubleBeds < 0) {
            throw InvalidCapacityException::becauseNegative('doubleBeds', $this->doubleBeds);
        }
    }

    public function bedrooms(): int
    {
        return $this->bedrooms;
    }

    public function bathrooms(): int
    {
        return $this->bathrooms;
    }

    public function maxGuests(): int
    {
        return $this->maxGuests;
    }

    public function singleBeds(): int
    {
        return $this->singleBeds;
    }

    public function doubleBeds(): int
    {
        return $this->doubleBeds;
    }
}
