<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Exception;

final class InvalidGuestCountException extends \DomainException
{
    public static function becauseNull(): self
    {
        return new self('Guest count is required.');
    }

    public static function becauseNotPositive(int $value): self
    {
        return new self(\sprintf('Guest count must be at least 1, got %d.', $value));
    }

    public static function becauseTooLarge(int $value): self
    {
        return new self(\sprintf('Guest count must not exceed %d, got %d.', 100, $value));
    }
}
