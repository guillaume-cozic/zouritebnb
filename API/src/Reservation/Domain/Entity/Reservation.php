<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Event\ReservationCreated;
use App\Reservation\Domain\Exception\InvalidReservationStateException;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\Event\ReservationCancelled;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Shared\Domain\Event\ReservationRefused;
use App\Shared\Domain\Event\ReservationRequested;
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
        private readonly ReservationPrice $price,
        private readonly ?Uuid $guestUserId = null,
    ) {
    }

    public static function create(
        ReservationId $id,
        Uuid $accommodationId,
        Uuid $teamId,
        DateRange $dateRange,
        GuestName $guestName,
        ReservationPrice $price,
    ): self {
        $reservation = new self(
            id: $id,
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: $dateRange,
            guestName: $guestName,
            status: ReservationStatus::Confirmed,
            price: $price,
        );
        $reservation->recordEvent(new ReservationCreated($id->toUuid()));
        $reservation->recordEvent(new ReservationConfirmed($id->toUuid()));

        return $reservation;
    }

    public static function request(
        ReservationId $id,
        Uuid $accommodationId,
        Uuid $teamId,
        DateRange $dateRange,
        GuestName $guestName,
        ReservationPrice $price,
        Uuid $guestUserId,
        ?string $note = null,
        ?string $paymentIntentId = null,
    ): self {
        $reservation = new self(
            id: $id,
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: $dateRange,
            guestName: $guestName,
            status: ReservationStatus::Pending,
            price: $price,
            guestUserId: $guestUserId,
        );
        $reservation->recordEvent(new ReservationCreated($id->toUuid()));
        $reservation->recordEvent(new ReservationRequested($id->toUuid(), $guestUserId, $note, $paymentIntentId));

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

    public function getPrice(): ReservationPrice
    {
        return $this->price;
    }

    public function getGuestUserId(): ?Uuid
    {
        return $this->guestUserId;
    }

    public function confirm(): void
    {
        if (ReservationStatus::Confirmed === $this->status) {
            throw InvalidReservationStateException::becauseAlreadyConfirmed();
        }
        if (ReservationStatus::Cancelled === $this->status) {
            throw InvalidReservationStateException::becauseCancelledCannotBeConfirmed();
        }
        if (ReservationStatus::Refused === $this->status) {
            throw InvalidReservationStateException::becauseRefusedCannotBeConfirmed();
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

    public function refuse(bool $automatic = false): void
    {
        if (ReservationStatus::Refused === $this->status) {
            throw InvalidReservationStateException::becauseAlreadyRefused();
        }
        if (ReservationStatus::Pending !== $this->status) {
            throw InvalidReservationStateException::becauseOnlyPendingCanBeRefused();
        }

        $this->status = ReservationStatus::Refused;
        $this->recordEvent(new ReservationRefused($this->id->toUuid(), $automatic));
    }
}
