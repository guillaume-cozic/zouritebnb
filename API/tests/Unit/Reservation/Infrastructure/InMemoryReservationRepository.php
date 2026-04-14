<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Port\ReservationRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryReservationRepository implements ReservationRepository
{
    /** @var Reservation[] */
    private array $reservations = [];

    public function save(Reservation $reservation): void
    {
        $this->reservations[$reservation->getId()->toString()] = $reservation;
    }

    public function ofId(ReservationId $id): ?Reservation
    {
        return $this->reservations[$id->toString()] ?? null;
    }

    public function list(
        Uuid $teamId,
        ?Uuid $accommodationId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): array {
        $result = [];
        foreach ($this->reservations as $reservation) {
            if (!$reservation->getTeamId()->equals($teamId)) {
                continue;
            }
            if (null !== $accommodationId && !$reservation->getAccommodationId()->equals($accommodationId)) {
                continue;
            }
            if (null !== $from && $reservation->getDateRange()->checkOut() <= $from) {
                continue;
            }
            if (null !== $to && $reservation->getDateRange()->checkIn() >= $to) {
                continue;
            }
            $result[] = $reservation;
        }

        return $result;
    }
}
