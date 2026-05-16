<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Command\ExpirePendingReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use Symfony\Component\Uid\Uuid;

/**
 * Expires a pending reservation after a 24h timeout. No-op if:
 *  - the reservation no longer exists,
 *  - it is no longer in Pending status (host already acted),
 *  - or the timeout window has not yet elapsed (e.g. message replayed early in sync transport).
 *
 * The handler is idempotent and safe to invoke multiple times.
 */
final readonly class ExpirePendingReservation
{
    public const int TIMEOUT_HOURS = 24;

    public function __construct(
        private ReservationRepository $repository,
        private Clock $clock,
        private EventBus $eventBus,
    ) {
    }

    public function handle(ExpirePendingReservationCommand $command): void
    {
        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($command->reservationId)));

        if (null === $reservation) {
            return;
        }
        if (ReservationStatus::Pending !== $reservation->getStatus()) {
            return;
        }

        $deadline = $command->dispatchedAt->modify(\sprintf('+%d hours', self::TIMEOUT_HOURS));
        if ($this->clock->now() < $deadline) {
            return;
        }

        $reservation->refuse(automatic: true);

        $this->repository->save($reservation);
        $this->eventBus->dispatch($reservation->releaseEvents());
    }
}
