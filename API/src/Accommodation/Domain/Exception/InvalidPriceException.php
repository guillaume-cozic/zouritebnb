<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidPriceException extends \DomainException
{
    public static function becauseNull(): self
    {
        return new self('Price is required.');
    }

    public static function becauseNegativeOrZero(float $price): self
    {
        return new self(\sprintf('Price must be strictly positive, got %s.', $price));
    }
}
