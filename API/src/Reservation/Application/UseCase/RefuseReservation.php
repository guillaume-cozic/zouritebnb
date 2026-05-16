<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Command\RefuseReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Domain\Port\EventBus;
use Symfony\Component\Uid\Uuid;

final readonly class RefuseReservation
{
    public function __construct(
        private ReservationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(RefuseReservationCommand $command): void
    {
        $id = new ReservationId(Uuid::fromString($command->reservationId));
        $reservation = $this->repository->ofId($id);

        if (null === $reservation) {
            throw ReservationNotFoundException::becauseId($command->reservationId);
        }

        $reservation->refuse($command->automatic);

        $this->repository->save($reservation);
        $this->eventBus->dispatch($reservation->releaseEvents());
    }
}
