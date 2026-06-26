<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidAccommodationTypeException;

/**
 * Category of an accommodation, chosen by the host. Optional (null = unspecified).
 */
enum AccommodationType: string
{
    case Apartment = 'apartment';
    case House = 'house';
    case Villa = 'villa';
    case Studio = 'studio';
    case Room = 'room';
    case Bungalow = 'bungalow';

    /**
     * Returns null for null/empty input (unspecified type), the matching case
     * otherwise, and throws on an unknown non-empty value.
     */
    public static function fromString(?string $value): ?self
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return self::tryFrom($value) ?? throw InvalidAccommodationTypeException::becauseUnknown($value);
    }
}
