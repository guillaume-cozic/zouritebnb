<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidAddressException extends \DomainException
{
    public static function becauseEmptyStreet(): self
    {
        return new self('Street is required.');
    }

    public static function becauseEmptyCity(): self
    {
        return new self('City is required.');
    }

    public static function becauseEmptyCountry(): self
    {
        return new self('Country is required.');
    }
}
