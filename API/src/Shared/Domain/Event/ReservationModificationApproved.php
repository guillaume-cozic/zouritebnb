<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published when the host approves a requested date change:
 * the reservation now carries the new dates and recomputed price.
 */
final readonly class ReservationModificationApproved implements DomainEvent
{
    public function __construct(public Uuid $reservationId)
    {
    }
}
