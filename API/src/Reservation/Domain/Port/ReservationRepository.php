<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Port;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use Symfony\Component\Uid\Uuid;

interface ReservationRepository
{
    public function save(Reservation $reservation): void;

    public function ofId(ReservationId $id): ?Reservation;

    /**
     * Lists the reservations visible to a user: those belonging to the user's team
     * (as host) or those where the user is the guest.
     *
     * @return Reservation[]
     */
    public function list(
        Uuid $teamId,
        Uuid $guestUserId,
        ?Uuid $accommodationId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): array;
}
