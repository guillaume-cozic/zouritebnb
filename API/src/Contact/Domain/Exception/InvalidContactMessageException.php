<?php

declare(strict_types=1);

namespace App\Contact\Domain\Exception;

final class InvalidContactMessageException extends \DomainException
{
    public static function becauseEmptyName(): self
    {
        return new self('Name is required.');
    }

    public static function becauseInvalidEmail(string $email): self
    {
        return new self(\sprintf('Email "%s" is not a valid email address.', $email));
    }

    public static function becauseEmptySubject(): self
    {
        return new self('Subject is required.');
    }

    public static function becauseEmptyMessage(): self
    {
        return new self('Message is required.');
    }
}
