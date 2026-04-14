<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidWeeklyPromotionException extends \DomainException
{
    public static function becauseOutOfBounds(float $value): self
    {
        return new self(\sprintf('Weekly promotion percentage must be strictly greater than 0 and less than or equal to 100, got %s.', $value));
    }
}
