<?php

declare(strict_types=1);

namespace App\Payment\Domain\Entity;

use App\Payment\Domain\Event\PaymentAuthorized;
use App\Payment\Domain\Event\PaymentCancelled;
use App\Payment\Domain\Event\PaymentCaptured;
use App\Payment\Domain\Event\PaymentFailed;
use App\Payment\Domain\Event\PaymentLinkedToReservation;
use App\Payment\Domain\Exception\InvalidPaymentException;
use App\Shared\Domain\Entity\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Payment extends AggregateRoot
{
    private PaymentStatus $status;
    private ?Uuid $reservationId;
    private readonly Uuid $id;
    private readonly string $stripePaymentIntentId;
    private readonly int $amountCents;
    private readonly string $currency;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(
        Uuid $id,
        ?Uuid $reservationId,
        string $stripePaymentIntentId,
        PaymentStatus $status,
        int $amountCents,
        string $currency,
        \DateTimeImmutable $createdAt,
    ) {
        $stripePaymentIntentId = trim($stripePaymentIntentId);
        if ('' === $stripePaymentIntentId) {
            throw InvalidPaymentException::becausePaymentIntentIdIsBlank();
        }

        if ($amountCents <= 0) {
            throw InvalidPaymentException::becauseAmountIsNotPositive($amountCents);
        }

        $currency = strtolower($currency);
        if (3 !== \strlen($currency) || !ctype_alpha($currency)) {
            throw InvalidPaymentException::becauseCurrencyIsInvalid($currency);
        }

        $this->id = $id;
        $this->reservationId = $reservationId;
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        $this->status = $status;
        $this->amountCents = $amountCents;
        $this->currency = $currency;
        $this->createdAt = $createdAt;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getReservationId(): ?Uuid
    {
        return $this->reservationId;
    }

    public function getStripePaymentIntentId(): string
    {
        return $this->stripePaymentIntentId;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markAuthorized(): void
    {
        if (PaymentStatus::Authorized === $this->status) {
            return;
        }
        $this->ensureCanTransitionTo(PaymentStatus::Authorized, [PaymentStatus::Pending]);
        $this->status = PaymentStatus::Authorized;
        $this->recordEvent(new PaymentAuthorized($this->id, $this->stripePaymentIntentId));
    }

    public function markCaptured(): void
    {
        if (PaymentStatus::Captured === $this->status) {
            return;
        }
        $this->ensureCanTransitionTo(PaymentStatus::Captured, [PaymentStatus::Pending, PaymentStatus::Authorized]);
        $this->status = PaymentStatus::Captured;
        $this->recordEvent(new PaymentCaptured($this->id, $this->stripePaymentIntentId));
    }

    public function markCancelled(): void
    {
        if (PaymentStatus::Cancelled === $this->status) {
            return;
        }
        $this->ensureCanTransitionTo(PaymentStatus::Cancelled, [PaymentStatus::Pending, PaymentStatus::Authorized, PaymentStatus::Failed]);
        $this->status = PaymentStatus::Cancelled;
        $this->recordEvent(new PaymentCancelled($this->id, $this->stripePaymentIntentId));
    }

    public function markFailed(): void
    {
        if (PaymentStatus::Failed === $this->status) {
            return;
        }
        $this->ensureCanTransitionTo(PaymentStatus::Failed, [PaymentStatus::Pending, PaymentStatus::Authorized]);
        $this->status = PaymentStatus::Failed;
        $this->recordEvent(new PaymentFailed($this->id, $this->stripePaymentIntentId));
    }

    public function linkReservation(Uuid $reservationId): void
    {
        if (null !== $this->reservationId && $this->reservationId->equals($reservationId)) {
            return;
        }

        $this->reservationId = $reservationId;
        $this->recordEvent(new PaymentLinkedToReservation($this->id, $reservationId));
    }

    /**
     * @param PaymentStatus[] $allowedFrom
     */
    private function ensureCanTransitionTo(PaymentStatus $to, array $allowedFrom): void
    {
        if (!\in_array($this->status, $allowedFrom, true)) {
            throw InvalidPaymentException::becauseTransitionIsInvalid($this->status, $to);
        }
    }
}
