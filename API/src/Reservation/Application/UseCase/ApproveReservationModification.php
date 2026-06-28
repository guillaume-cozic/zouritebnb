<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Command\ApproveReservationModificationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Exception\InvalidReservationException;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Domain\Port\EventBus;
use Symfony\Component\Uid\Uuid;

final readonly class ApproveReservationModification
{
    public function __construct(
        private ReservationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(ApproveReservationModificationCommand $command): void
    {
        $id = new ReservationId(Uuid::fromString($command->reservationId));
        $reservation = $this->repository->ofId($id);
        if (null === $reservation) {
            throw ReservationNotFoundException::becauseId($command->reservationId);
        }

        // Guard against another booking having taken the proposed dates since the request.
        $pending = $reservation->getPendingModification();
        if (null !== $pending && $this->repository->hasOverlappingReservation($reservation->getAccommodationId(), $pending->dateRange, $id)) {
            throw InvalidReservationException::becauseDatesUnavailable();
        }

        $reservation->approveModification();

        $this->repository->save($reservation);
        $this->eventBus->dispatch($reservation->releaseEvents());
    }
}
