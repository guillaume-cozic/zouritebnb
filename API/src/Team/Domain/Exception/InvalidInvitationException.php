<?php

declare(strict_types=1);

namespace App\Team\Domain\Exception;

final class InvalidInvitationException extends \DomainException
{
    public static function becauseEmptyEmail(): self
    {
        return new self('Invitation email must not be empty.');
    }

    public static function becauseInvalidEmailFormat(string $email): self
    {
        return new self(\sprintf('Invitation email "%s" is not a valid email address.', $email));
    }

    public static function becauseAlreadyFinalized(): self
    {
        return new self('Invitation is already finalized and cannot be cancelled.');
    }

    public static function becauseAlreadyInvited(string $email): self
    {
        return new self(\sprintf('A pending invitation already exists for email "%s".', $email));
    }

    public static function becauseNotFound(string $id): self
    {
        return new self(\sprintf('Invitation "%s" was not found.', $id));
    }
}
