<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Messenger;

use App\Reservation\Application\UseCase\ExpirePendingReservation;
use App\Shared\Domain\Event\ReservationRequested;
use App\Shared\Domain\Port\Clock;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Listens for ReservationRequested and schedules an expiration check
 * to fire after the configured timeout (default 24h).
 */
final readonly class ScheduleReservationExpiry
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private Clock $clock,
    ) {
    }

    public function __invoke(ReservationRequested $event): void
    {
        $now = $this->clock->now();

        $this->messageBus->dispatch(
            new ExpireReservationMessage(
                reservationId: $event->reservationId,
                dispatchedAt: $now,
            ),
            [new DelayStamp(ExpirePendingReservation::TIMEOUT_HOURS * 3600 * 1000)],
        );
    }
}
