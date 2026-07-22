<?php

declare(strict_types=1);

namespace App\Donation\Domain\Entity;

use App\Donation\Domain\Event\DonationCancelled;
use App\Donation\Domain\Event\DonationFailed;
use App\Donation\Domain\Event\DonationPaid;
use App\Donation\Domain\Exception\InvalidDonationException;
use App\Shared\Domain\Entity\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Donation extends AggregateRoot
{
    /** Minimum donation amount: 1 euro. */
    private const int MIN_AMOUNT_CENTS = 100;

    /** Maximum donation amount: 10 000 euros. */
    private const int MAX_AMOUNT_CENTS = 1_000_000;

    private DonationStatus $status;
    private readonly Uuid $id;
    private readonly Uuid $solidarityProjectId;
    private readonly string $stripePaymentIntentId;
    private readonly int $amountCents;
    private readonly string $currency;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(
        Uuid $id,
        Uuid $solidarityProjectId,
        string $stripePaymentIntentId,
        DonationStatus $status,
        int $amountCents,
        string $currency,
        \DateTimeImmutable $createdAt,
    ) {
        $stripePaymentIntentId = trim($stripePaymentIntentId);
        if ('' === $stripePaymentIntentId) {
            throw InvalidDonationException::becauseEmptyPaymentIntentId();
        }

        self::ensureAmountWithinBounds($amountCents);

        $this->id = $id;
        $this->solidarityProjectId = $solidarityProjectId;
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        $this->status = $status;
        $this->amountCents = $amountCents;
        $this->currency = $currency;
        $this->createdAt = $createdAt;
    }

    /**
     * The donation amount is freely chosen by the donor, so the domain bounds it.
     * Exposed statically so use cases can validate before calling the gateway.
     */
    public static function ensureAmountWithinBounds(int $amountCents): void
    {
        if ($amountCents < self::MIN_AMOUNT_CENTS) {
            throw InvalidDonationException::becauseAmountBelowMinimum($amountCents);
        }

        if ($amountCents > self::MAX_AMOUNT_CENTS) {
            throw InvalidDonationException::becauseAmountAboveMaximum($amountCents);
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSolidarityProjectId(): Uuid
    {
        return $this->solidarityProjectId;
    }

    public function getStripePaymentIntentId(): string
    {
        return $this->stripePaymentIntentId;
    }

    public function getStatus(): DonationStatus
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

    public function markPaid(): void
    {
        if (DonationStatus::Paid === $this->status) {
            return;
        }
        $this->ensureCanTransitionTo(DonationStatus::Paid, [DonationStatus::Pending]);
        $this->status = DonationStatus::Paid;
        $this->recordEvent(new DonationPaid($this->id, $this->stripePaymentIntentId));
    }

    public function markFailed(): void
    {
        if (DonationStatus::Failed === $this->status) {
            return;
        }
        $this->ensureCanTransitionTo(DonationStatus::Failed, [DonationStatus::Pending]);
        $this->status = DonationStatus::Failed;
        $this->recordEvent(new DonationFailed($this->id, $this->stripePaymentIntentId));
    }

    public function markCancelled(): void
    {
        if (DonationStatus::Cancelled === $this->status) {
            return;
        }
        $this->ensureCanTransitionTo(DonationStatus::Cancelled, [DonationStatus::Pending, DonationStatus::Failed]);
        $this->status = DonationStatus::Cancelled;
        $this->recordEvent(new DonationCancelled($this->id, $this->stripePaymentIntentId));
    }

    /**
     * @param DonationStatus[] $allowedFrom
     */
    private function ensureCanTransitionTo(DonationStatus $to, array $allowedFrom): void
    {
        if (!\in_array($this->status, $allowedFrom, true)) {
            throw InvalidDonationException::becauseTransitionIsInvalid($this->status, $to);
        }
    }
}
