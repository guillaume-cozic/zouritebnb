<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published by the Reservation context when a reservation is
 * cancelled (by guest, host, or auto-expiry). Consumers in other contexts can
 * react — e.g. Payment cancels the held authorization.
 */
final readonly class ReservationCancelled implements DomainEvent
{
    public function __construct(
        public Uuid $reservationId,
        public ?string $message = null,
    ) {
    }
}
