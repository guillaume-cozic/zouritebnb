<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Event\ReservationCancelled;
use App\Reservation\Domain\Event\ReservationConfirmed;
use App\Reservation\Domain\Event\ReservationCreated;
use App\Reservation\Domain\Exception\InvalidReservationStateException;
use App\Shared\Domain\Entity\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Reservation extends AggregateRoot
{
    public function __construct(
        private readonly ReservationId $id,
        private readonly Uuid $accommodationId,
        private readonly Uuid $teamId,
        private readonly DateRange $dateRange,
        private readonly GuestName $guestName,
        private ReservationStatus $status,
    ) {
    }

    public static function create(
        ReservationId $id,
        Uuid $accommodationId,
        Uuid $teamId,
        DateRange $dateRange,
        GuestName $guestName,
    ): self {
        $reservation = new self(
            id: $id,
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: $dateRange,
            guestName: $guestName,
            status: ReservationStatus::Confirmed,
        );
        $reservation->recordEvent(new ReservationCreated($id->toUuid()));
        $reservation->recordEvent(new ReservationConfirmed($id->toUuid()));

        return $reservation;
    }

    public function getId(): ReservationId
    {
        return $this->id;
    }

    public function getAccommodationId(): Uuid
    {
        return $this->accommodationId;
    }

    public function getTeamId(): Uuid
    {
        return $this->teamId;
    }

    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }

    public function getGuestName(): GuestName
    {
        return $this->guestName;
    }

    public function getStatus(): ReservationStatus
    {
        return $this->status;
    }

    public function confirm(): void
    {
        if (ReservationStatus::Confirmed === $this->status) {
            throw InvalidReservationStateException::becauseAlreadyConfirmed();
        }
        if (ReservationStatus::Cancelled === $this->status) {
            throw InvalidReservationStateException::becauseCancelledCannotBeConfirmed();
        }

        $this->status = ReservationStatus::Confirmed;
        $this->recordEvent(new ReservationConfirmed($this->id->toUuid()));
    }

    public function cancel(): void
    {
        if (ReservationStatus::Cancelled === $this->status) {
            throw InvalidReservationStateException::becauseAlreadyCancelled();
        }

        $this->status = ReservationStatus::Cancelled;
        $this->recordEvent(new ReservationCancelled($this->id->toUuid()));
    }
}
