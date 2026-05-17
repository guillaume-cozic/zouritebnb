<?php

declare(strict_types=1);

namespace App\Geography\Domain\Exception;

final class InvalidRegionException extends \DomainException
{
    public static function becauseCodeInvalid(string $code): self
    {
        return new self(\sprintf('Region code "%s" is invalid. Expected uppercase alphanumeric (with underscores) starting with a letter, 2 to 31 characters.', $code));
    }

    public static function becauseNameBlank(): self
    {
        return new self('Region name must not be blank.');
    }
}
