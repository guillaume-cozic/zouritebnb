<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidPricePeriodException extends \DomainException
{
    public static function becauseInvalidDate(): self
    {
        return new self('Price period dates must be valid calendar dates in Y-m-d format.');
    }

    public static function becauseEndBeforeStart(): self
    {
        return new self('Price period end date must be on or after its start date.');
    }

    public static function becauseNonPositivePrice(float $value): self
    {
        return new self(\sprintf('Price period nightly price must be strictly positive, got %s.', $value));
    }

    public static function becauseInvalidItem(): self
    {
        return new self('A price period collection only accepts PricePeriod instances.');
    }
}
