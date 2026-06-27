<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

final class ReviewNotFoundException extends \DomainException
{
    public static function becauseId(string $id): self
    {
        return new self(\sprintf('Review "%s" not found.', $id));
    }
}
