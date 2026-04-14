<?php

declare(strict_types=1);

namespace App\Reservation\Application\UseCase;

use App\Reservation\Domain\Command\CreateReservationCommand;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class CreateReservation
{
    public function __construct(
        private ReservationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(CreateReservationCommand $command): string
    {
        $reservation = Reservation::create(
            id: new ReservationId(UuidGenerator::generate()),
            accommodationId: $command->accommodationId,
            teamId: $command->teamId,
            dateRange: new DateRange($command->checkIn, $command->checkOut),
            guestName: new GuestName($command->guestName),
        );

        $this->repository->save($reservation);
        $this->eventBus->dispatch($reservation->releaseEvents());

        return $reservation->getId()->toString();
    }
}
