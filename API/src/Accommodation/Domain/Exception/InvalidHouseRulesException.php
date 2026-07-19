<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidHouseRulesException extends \DomainException
{
    public static function becauseNotesTooLong(int $length, int $maxLength): self
    {
        return new self(\sprintf('House rules notes cannot exceed %d characters, got %d.', $maxLength, $length));
    }
}
