<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Exception;

final class InvalidReservationIdException extends \DomainException
{
    public static function becauseNull(): self
    {
        return new self('Reservation id is required.');
    }
}
