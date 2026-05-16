<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Refused = 'refused';
}
