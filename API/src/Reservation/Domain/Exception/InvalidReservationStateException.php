<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Exception;

final class InvalidReservationStateException extends \DomainException
{
    public static function becauseAlreadyConfirmed(): self
    {
        return new self('Reservation is already confirmed.');
    }

    public static function becauseAlreadyCancelled(): self
    {
        return new self('Reservation is already cancelled.');
    }

    public static function becauseCancelledCannotBeConfirmed(): self
    {
        return new self('A cancelled reservation cannot be confirmed.');
    }

    public static function becauseRefusedCannotBeConfirmed(): self
    {
        return new self('A refused reservation cannot be confirmed.');
    }

    public static function becauseAlreadyRefused(): self
    {
        return new self('Reservation is already refused.');
    }

    public static function becauseOnlyPendingCanBeRefused(): self
    {
        return new self('Only a pending reservation can be refused.');
    }
}
