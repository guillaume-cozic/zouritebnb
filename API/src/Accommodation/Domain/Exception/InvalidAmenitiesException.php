<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidAmenitiesException extends \DomainException
{
    public static function becauseInvalidCode(mixed $code): self
    {
        return new self(\sprintf('Each amenity code must be a non-empty string, got "%s".', get_debug_type($code)));
    }

    public static function becauseEmptyCode(): self
    {
        return new self('Amenity code must not be empty.');
    }
}
