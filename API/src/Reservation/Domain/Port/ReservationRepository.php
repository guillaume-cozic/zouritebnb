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
     * @return Reservation[]
     */
    public function list(
        Uuid $teamId,
        ?Uuid $accommodationId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): array;
}
