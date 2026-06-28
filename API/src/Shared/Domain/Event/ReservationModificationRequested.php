<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published when a guest requests a change of dates on their
 * confirmed reservation. The host must approve or reject it.
 */
final readonly class ReservationModificationRequested implements DomainEvent
{
    public function __construct(public Uuid $reservationId)
    {
    }
}
