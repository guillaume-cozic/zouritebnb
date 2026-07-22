<?php

declare(strict_types=1);

namespace App\Donation\Domain\Exception;

use App\Donation\Domain\Entity\DonationStatus;

final class InvalidDonationException extends \DomainException
{
    public static function becauseAmountBelowMinimum(int $amountCents): self
    {
        return new self(\sprintf('Donation amount must be at least 100 cents (1 euro), got %d cents.', $amountCents));
    }

    public static function becauseAmountAboveMaximum(int $amountCents): self
    {
        return new self(\sprintf('Donation amount must not exceed 1000000 cents (10000 euros), got %d cents.', $amountCents));
    }

    public static function becauseEmptyPaymentIntentId(): self
    {
        return new self('Donation payment intent identifier must not be blank.');
    }

    public static function becauseSolidarityProjectNotDonatable(string $solidarityProjectId): self
    {
        return new self(\sprintf('Solidarity project "%s" does not exist or does not accept donations.', $solidarityProjectId));
    }

    public static function becauseTransitionIsInvalid(DonationStatus $from, DonationStatus $to): self
    {
        return new self(\sprintf('Cannot transition donation from "%s" to "%s".', $from->value, $to->value));
    }
}
