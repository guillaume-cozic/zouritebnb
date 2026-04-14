<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Exception;

final class InvalidDateRangeException extends \DomainException
{
    public static function becauseCheckOutNotAfterCheckIn(): self
    {
        return new self('Check-out date must be strictly after check-in date.');
    }
}
