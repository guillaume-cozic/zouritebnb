<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

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
        private readonly GuestCount $guestCount,
        private ReservationStatus $status,
        private readonly ReservationPrice $price,
        private readonly ?Uuid $guestUserId = null,
        private readonly CancellationPolicy $cancellationPolicy = CancellationPolicy::Flexible,
    ) {
    }

    public static function create(
        ReservationId $id,
        Uuid $accommodationId,
        Uuid $teamId,
        DateRange $dateRange,
        GuestName $guestName,
        GuestCount $guestCount,
        ReservationPrice $price,
        CancellationPolicy $cancellationPolicy = CancellationPolicy::Flexible,
    ): self {
        $reservation = new self(
            id: $id,
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: $dateRange,
            guestName: $guestName,
            guestCount: $guestCount,
            status: ReservationStatus::Confirmed,
            price: $price,
            cancellationPolicy: $cancellationPolicy,
        );
        $reservation->recordEvent(new ReservationConfirmed($id->toUuid()));

        return $reservation;
    }

    public static function request(
        ReservationId $id,
        Uuid $accommodationId,
        Uuid $teamId,
        DateRange $dateRange,
        GuestName $guestName,
        GuestCount $guestCount,
        ReservationPrice $price,
        Uuid $guestUserId,
        ?string $note = null,
        ?string $paymentIntentId = null,
        CancellationPolicy $cancellationPolicy = CancellationPolicy::Flexible,
        bool $instantBooking = false,
    ): self {
        $reservation = new self(
            id: $id,
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: $dateRange,
            guestName: $guestName,
            guestCount: $guestCount,
            status: ReservationStatus::Pending,
            price: $price,
            guestUserId: $guestUserId,
            cancellationPolicy: $cancellationPolicy,
        );
        $reservation->recordEvent(new ReservationRequested($id->toUuid(), $guestUserId, $note, $paymentIntentId, $instantBooking));

        // Instant booking: the request is auto-confirmed in the same transaction.
        // Releasing ReservationRequested first keeps payment linking (which keys off
        // the payment intent id on that event) ordered before the capture triggered
        // by ReservationConfirmed.
        if ($instantBooking) {
            $reservation->confirm();
        }

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

    public function getGuestCount(): GuestCount
    {
        return $this->guestCount;
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

    public function getCancellationPolicy(): CancellationPolicy
    {
        return $this->cancellationPolicy;
    }

    /**
     * A reservation can be cancelled only while it is still pending or confirmed
     * and the stay has not started yet (check-in strictly in the future).
     */
    public function isCancellable(\DateTimeImmutable $now): bool
    {
        return \in_array($this->status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)
            && $now < $this->dateRange->checkIn();
    }

    /**
     * What the guest would be refunded if they cancelled at $now. A pending request
     * was never captured, so cancelling it always returns everything; a confirmed
     * reservation follows the snapshotted policy based on the time left before check-in.
     */
    public function refundBreakdown(\DateTimeImmutable $now): RefundBreakdown
    {
        $totalPaid = round($this->price->totalPrice + $this->price->commissionAmount + $this->price->donationAmount, 2);

        $percentage = match ($this->status) {
            ReservationStatus::Pending => 100,
            ReservationStatus::Confirmed => $this->cancellationPolicy->refundPercentage(
                $this->dateRange->checkIn()->getTimestamp() - $now->getTimestamp(),
            ),
            default => 0,
        };

        return new RefundBreakdown(
            policy: $this->cancellationPolicy,
            totalPaid: $totalPaid,
            refundAmount: round($totalPaid * $percentage / 100, 2),
            refundPercentage: $percentage,
        );
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

    public function cancel(\DateTimeImmutable $now, ?string $message = null): void
    {
        if (ReservationStatus::Cancelled === $this->status) {
            throw InvalidReservationStateException::becauseAlreadyCancelled();
        }
        if (ReservationStatus::Refused === $this->status) {
            throw InvalidReservationStateException::becauseRefusedCannotBeCancelled();
        }
        if ($now >= $this->dateRange->checkIn()) {
            throw InvalidReservationStateException::becauseStayAlreadyStarted();
        }

        $this->status = ReservationStatus::Cancelled;
        $this->recordEvent(new ReservationCancelled($this->id->toUuid(), $message));
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
