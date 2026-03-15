<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidCapacityException extends \DomainException
{
    public static function becauseNegative(string $field, int $value): self
    {
        return new self(\sprintf('The field "%s" must be >= 0, got %d.', $field, $value));
    }
}
