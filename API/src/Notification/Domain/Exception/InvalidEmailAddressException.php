<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

final class InvalidEmailAddressException extends \DomainException
{
    public static function becauseNull(): self
    {
        return new self('Email address is required.');
    }

    public static function becauseInvalidFormat(string $value): self
    {
        return new self(\sprintf('Email address "%s" is not a valid address.', $value));
    }
}
