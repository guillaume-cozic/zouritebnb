<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Exception\InvalidGuestCountException;

final readonly class GuestCount
{
    private const int MAX = 100;

    private int $value;

    public function __construct(?int $value)
    {
        if (null === $value) {
            throw InvalidGuestCountException::becauseNull();
        }

        if ($value < 1) {
            throw InvalidGuestCountException::becauseNotPositive($value);
        }

        if ($value > self::MAX) {
            throw InvalidGuestCountException::becauseTooLarge($value);
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }
}
