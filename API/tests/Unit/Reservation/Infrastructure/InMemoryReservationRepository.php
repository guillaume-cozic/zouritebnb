<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationStatus;
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
        Uuid $guestUserId,
        ?Uuid $accommodationId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): array {
        $result = [];
        foreach ($this->reservations as $reservation) {
            $isTeamHost = $reservation->getTeamId()->equals($teamId);
            $isGuest = null !== $reservation->getGuestUserId() && $reservation->getGuestUserId()->equals($guestUserId);
            if (!$isTeamHost && !$isGuest) {
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

    public function busyRanges(Uuid $accommodationId, \DateTimeImmutable $from): array
    {
        $result = [];
        foreach ($this->reservations as $reservation) {
            if (!$reservation->getAccommodationId()->equals($accommodationId)) {
                continue;
            }
            if (!\in_array($reservation->getStatus(), [ReservationStatus::Pending, ReservationStatus::Confirmed], true)) {
                continue;
            }
            if ($reservation->getDateRange()->checkOut() <= $from) {
                continue;
            }
            $result[] = $reservation->getDateRange();
        }

        return $result;
    }
}
