<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

final class InvalidRatingException extends \DomainException
{
    public static function becauseNull(): self
    {
        return new self('Rating is required.');
    }

    public static function becauseOutOfBounds(int $value): self
    {
        return new self(\sprintf('Rating must be an integer between 1 and 5, got %d.', $value));
    }
}
