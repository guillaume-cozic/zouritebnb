<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

final class InvalidReviewCommentException extends \DomainException
{
    public static function becauseNull(): self
    {
        return new self('Review comment is required.');
    }

    public static function becauseTooShort(int $length, int $minLength): self
    {
        return new self(\sprintf('Review comment must be at least %d characters long, got %d.', $minLength, $length));
    }
}
