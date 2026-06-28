<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidDynamicPricingException extends \DomainException
{
    public static function becauseWeekendSurchargeOutOfBounds(float $value): self
    {
        return new self(\sprintf('Weekend surcharge percentage must be strictly greater than 0 and at most 500, got %s.', $value));
    }

    public static function becauseLastMinuteDiscountOutOfBounds(float $value): self
    {
        return new self(\sprintf('Last-minute discount percentage must be strictly greater than 0 and at most 100, got %s.', $value));
    }

    public static function becauseLastMinuteDaysNotPositive(int $value): self
    {
        return new self(\sprintf('Last-minute window (in days) must be at least 1, got %d.', $value));
    }

    public static function becauseLastMinuteIncomplete(): self
    {
        return new self('Last-minute pricing requires both a discount percentage and a days window, or neither.');
    }
}
