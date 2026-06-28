<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published when the host rejects a requested date change:
 * the reservation keeps its original dates and price.
 */
final readonly class ReservationModificationRejected implements DomainEvent
{
    public function __construct(public Uuid $reservationId)
    {
    }
}
