<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Exception;

final class InvalidMessageException extends \DomainException
{
    public static function becauseIdNull(): self
    {
        return new self('Message id is required.');
    }

    public static function becauseBodyNull(): self
    {
        return new self('Message body is required.');
    }

    public static function becauseBodyEmpty(): self
    {
        return new self('Message body must not be empty.');
    }

    public static function becauseBodyTooLong(int $maxLength): self
    {
        return new self(\sprintf('Message body must not exceed %d characters.', $maxLength));
    }
}
