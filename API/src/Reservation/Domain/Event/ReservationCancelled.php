<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class ReservationCancelled implements DomainEvent
{
    public function __construct(public Uuid $reservationId)
    {
    }
}
