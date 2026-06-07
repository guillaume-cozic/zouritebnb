<?php

declare(strict_types=1);

namespace App\Review\Domain\Entity;

use App\Review\Domain\Exception\InvalidRatingException;

final readonly class Rating
{
    private const int MIN = 1;
    private const int MAX = 5;

    public function __construct(private ?int $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->value) {
            throw InvalidRatingException::becauseNull();
        }
        if ($this->value < self::MIN || $this->value > self::MAX) {
            throw InvalidRatingException::becauseOutOfBounds($this->value);
        }
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
