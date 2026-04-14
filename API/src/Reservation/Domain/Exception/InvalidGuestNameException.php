<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Exception;

final class InvalidGuestNameException extends \DomainException
{
    public static function becauseEmpty(): self
    {
        return new self('Guest name must not be empty.');
    }

    public static function becauseTooLong(int $length): self
    {
        return new self(\sprintf('Guest name must not exceed 255 characters, got %d.', $length));
    }
}
