<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidStayConstraintsException extends \DomainException
{
    public static function becauseNotPositive(): self
    {
        return new self('Minimum and maximum nights must be greater than or equal to 1.');
    }

    public static function becauseMinGreaterThanMax(int $minNights, int $maxNights): self
    {
        return new self(\sprintf('Minimum nights (%d) cannot be greater than maximum nights (%d).', $minNights, $maxNights));
    }
}
