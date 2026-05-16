<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published by the Reservation context when a pending reservation
 * is refused — either manually by a host or automatically after the 24h timeout.
 */
final readonly class ReservationRefused implements DomainEvent
{
    public function __construct(
        public Uuid $reservationId,
        public bool $isAutomatic = false,
    ) {
    }
}
