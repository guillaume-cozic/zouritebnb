<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Exception\InvalidReservationStateException;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\Event\ReservationCancelled;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Shared\Domain\Event\ReservationModificationApproved;
use App\Shared\Domain\Event\ReservationModificationRejected;
use App\Shared\Domain\Event\ReservationModificationRequested;
use App\Shared\Domain\Event\ReservationRefused;
use App\Shared\Domain\Event\ReservationRequested;
use Symfony\Component\Uid\Uuid;

final class Reservation extends AggregateRoot
{
    public function __construct(
        private readonly ReservationId $id,
        private readonly Uuid $accommodationId,
        private readonly Uuid $teamId,
        private DateRange $dateRange,
        private readonly GuestName $guestName,
        private readonly GuestCount $guestCount,
        private ReservationStatus $status,
        private ReservationPrice $price,
        private readonly ?Uuid $guestUserId = null,
        private readonly CancellationPolicy $cancellationPolicy = CancellationPolicy::Flexible,
        private bool $cancelledByHost = false,
        private ?PendingModification $pendingModification = null,
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
    /**
     * What the guest is refunded if the reservation is cancelled at $now.
     *
     * A host-initiated cancellation always fully refunds the guest (full
     * compensation), whatever the policy — both as a live preview ($byHost) and
     * once it has actually been cancelled by the host. A guest cancellation
     * follows the snapshotted policy based on the time left before check-in.
     */
    public function refundBreakdown(\DateTimeImmutable $now, bool $byHost = false): RefundBreakdown
    {
        $totalPaid = round($this->price->totalPrice + $this->price->commissionAmount + $this->price->donationAmount, 2);

        $hostFullRefund = ($byHost && ReservationStatus::Confirmed === $this->status)
            || (ReservationStatus::Cancelled === $this->status && $this->cancelledByHost);

        $percentage = match (true) {
            ReservationStatus::Pending === $this->status => 100,
            $hostFullRefund => 100,
            ReservationStatus::Confirmed === $this->status => $this->cancellationPolicy->refundPercentage(
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

    public function getPendingModification(): ?PendingModification
    {
        return $this->pendingModification;
    }

    /**
     * A confirmed, not-yet-started reservation can have a date change requested by the
     * guest. The proposal (new range + recomputed price) waits for the host's approval;
     * re-requesting replaces any previous pending proposal.
     */
    public function requestModification(DateRange $dateRange, ReservationPrice $price, \DateTimeImmutable $now): void
    {
        if (ReservationStatus::Confirmed !== $this->status) {
            throw InvalidReservationStateException::becauseOnlyConfirmedCanBeModified();
        }
        if ($now >= $this->dateRange->checkIn()) {
            throw InvalidReservationStateException::becauseStayAlreadyStartedForModification();
        }

        $this->pendingModification = new PendingModification($dateRange, $price);
        $this->recordEvent(new ReservationModificationRequested($this->id->toUuid()));
    }

    public function approveModification(): void
    {
        if (null === $this->pendingModification) {
            throw InvalidReservationStateException::becauseNoPendingModification();
        }

        $this->dateRange = $this->pendingModification->dateRange;
        $this->price = $this->pendingModification->price;
        $this->pendingModification = null;
        $this->recordEvent(new ReservationModificationApproved($this->id->toUuid()));
    }

    public function rejectModification(): void
    {
        if (null === $this->pendingModification) {
            throw InvalidReservationStateException::becauseNoPendingModification();
        }

        $this->pendingModification = null;
        $this->recordEvent(new ReservationModificationRejected($this->id->toUuid()));
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

    public function cancel(\DateTimeImmutable $now, ?string $message = null, bool $byHost = false): void
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
        if ($byHost && (null === $message || '' === trim($message))) {
            throw InvalidReservationStateException::becauseHostCancellationRequiresMessage();
        }

        // Snapshot the refund terms before mutating the status: once cancelled, the
        // breakdown no longer reflects what the guest was owed at cancellation time.
        $refundPercentage = $this->refundBreakdown($now, $byHost)->refundPercentage;

        $this->status = ReservationStatus::Cancelled;
        $this->cancelledByHost = $byHost;
        $this->pendingModification = null;
        $this->recordEvent(new ReservationCancelled($this->id->toUuid(), $message, $refundPercentage));
    }

    public function isCancelledByHost(): bool
    {
        return $this->cancelledByHost;
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
