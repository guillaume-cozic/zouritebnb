<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Port;

use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use Symfony\Component\Uid\Uuid;

interface ReservationRepository
{
    public function save(Reservation $reservation): void;

    public function ofId(ReservationId $id): ?Reservation;

    /**
     * Lists the date ranges that currently block availability for an accommodation:
     * reservations in "pending" or "confirmed" status whose stay is not over yet
     * (checkOut strictly after $from). Used to mark unavailable dates publicly.
     *
     * @return DateRange[]
     */
    public function busyRanges(Uuid $accommodationId, \DateTimeImmutable $from): array;

    /**
     * Tells whether the accommodation already has a "pending" or "confirmed"
     * reservation overlapping the given date range. The departure day of an
     * existing stay does not count as an overlap (same-day turnover is allowed).
     * An optional reservation id is excluded from the check (used when re-pricing a
     * date change for an existing reservation).
     */
    public function hasOverlappingReservation(Uuid $accommodationId, DateRange $dateRange, ?ReservationId $excludeReservationId = null): bool;

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
