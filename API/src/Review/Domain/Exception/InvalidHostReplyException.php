<?php

declare(strict_types=1);

namespace App\Review\Domain\Exception;

final class InvalidHostReplyException extends \DomainException
{
    public static function becauseEmpty(): self
    {
        return new self('A host reply cannot be empty.');
    }

    public static function becauseTooLong(int $length, int $max): self
    {
        return new self(\sprintf('A host reply cannot exceed %d characters, got %d.', $max, $length));
    }
}
