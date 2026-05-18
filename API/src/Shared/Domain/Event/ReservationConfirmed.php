<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published by the Reservation context when the host (or any
 * confirmation path) confirms a reservation. Consumers in other contexts can
 * react — e.g. Payment captures the held authorization.
 */
final readonly class ReservationConfirmed implements DomainEvent
{
    public function __construct(public Uuid $reservationId)
    {
    }
}
