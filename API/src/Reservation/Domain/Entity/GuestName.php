<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Exception\InvalidGuestNameException;

final readonly class GuestName
{
    private const int MAX_LENGTH = 255;

    private string $value;

    public function __construct(?string $value)
    {
        $trimmed = null === $value ? '' : trim($value);

        if ('' === $trimmed) {
            throw InvalidGuestNameException::becauseEmpty();
        }

        $length = mb_strlen($trimmed);
        if ($length > self::MAX_LENGTH) {
            throw InvalidGuestNameException::becauseTooLong($length);
        }

        $this->value = $trimmed;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
