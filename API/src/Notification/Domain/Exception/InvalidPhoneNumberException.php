<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

final class InvalidPhoneNumberException extends \DomainException
{
    public static function becauseNull(): self
    {
        return new self('Phone number is required.');
    }

    public static function becauseInvalidFormat(string $value): self
    {
        return new self(\sprintf('Phone number "%s" is not a valid number.', $value));
    }
}
