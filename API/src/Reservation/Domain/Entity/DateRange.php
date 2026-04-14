<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Exception\InvalidDateRangeException;

final readonly class DateRange
{
    public function __construct(
        private \DateTimeImmutable $checkIn,
        private \DateTimeImmutable $checkOut,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->checkOut <= $this->checkIn) {
            throw InvalidDateRangeException::becauseCheckOutNotAfterCheckIn();
        }
    }

    public function checkIn(): \DateTimeImmutable
    {
        return $this->checkIn;
    }

    public function checkOut(): \DateTimeImmutable
    {
        return $this->checkOut;
    }
}
