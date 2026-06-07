<?php

declare(strict_types=1);

namespace App\Review\Domain\Entity;

use App\Review\Domain\Exception\InvalidReviewCommentException;

final readonly class ReviewComment
{
    private const int MIN_LENGTH = 50;

    public function __construct(private ?string $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->value) {
            throw InvalidReviewCommentException::becauseNull();
        }

        $length = mb_strlen(trim($this->value));
        if ($length < self::MIN_LENGTH) {
            throw InvalidReviewCommentException::becauseTooShort($length, self::MIN_LENGTH);
        }
    }

    public function toString(): string
    {
        return $this->value;
    }
}
