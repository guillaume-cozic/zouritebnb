<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

final class ReviewNotAllowedException extends \DomainException
{
    public static function becauseStayNotCompleted(): self
    {
        return new self('A review can only be submitted after a confirmed stay has ended.');
    }

    public static function becauseReviewAlreadySubmitted(): self
    {
        return new self('A review has already been submitted for this stay.');
    }
}
