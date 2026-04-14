<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Exception;

final class ReservationNotFoundException extends \DomainException
{
    public static function becauseId(string $id): self
    {
        return new self(\sprintf('Reservation "%s" not found.', $id));
    }
}
