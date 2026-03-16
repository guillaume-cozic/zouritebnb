<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidCheckInOutException;

final readonly class CheckInOut
{
    public function __construct(
        private string $checkIn,
        private string $checkOut,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (!preg_match('/^\d{2}:\d{2}$/', $this->checkIn)) {
            throw InvalidCheckInOutException::becauseInvalidFormat($this->checkIn);
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $this->checkOut)) {
            throw InvalidCheckInOutException::becauseInvalidFormat($this->checkOut);
        }
    }

    public function checkIn(): string
    {
        return $this->checkIn;
    }

    public function checkOut(): string
    {
        return $this->checkOut;
    }
}
