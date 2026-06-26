<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidAccommodationTypeException extends \DomainException
{
    public static function becauseUnknown(?string $value): self
    {
        return new self(\sprintf('Unknown accommodation type "%s". Allowed values: apartment, house, villa, studio, room, bungalow.', $value ?? 'null'));
    }
}
