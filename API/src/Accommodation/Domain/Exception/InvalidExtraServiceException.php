<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidExtraServiceException extends \DomainException
{
    public static function becauseEmptyName(): self
    {
        return new self('Extra service name must not be empty.');
    }

    public static function becauseNameTooLong(int $length, int $maxLength): self
    {
        return new self(\sprintf('Extra service name must not exceed %d characters, got %d.', $maxLength, $length));
    }

    public static function becauseNonPositivePrice(float $value): self
    {
        return new self(\sprintf('Extra service price must be strictly positive, got %s.', $value));
    }

    public static function becauseNonBooleanBilledWithReservation(): self
    {
        return new self('Extra service billedWithReservation must be a boolean.');
    }

    public static function becauseInvalidItem(): self
    {
        return new self('An extra service collection only accepts ExtraService instances.');
    }
}
