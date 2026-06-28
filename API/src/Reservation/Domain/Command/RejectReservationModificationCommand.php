<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Command;

final readonly class RejectReservationModificationCommand
{
    public function __construct(public string $reservationId)
    {
    }
}
