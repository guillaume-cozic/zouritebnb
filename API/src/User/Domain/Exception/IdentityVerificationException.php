<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class IdentityVerificationException extends \DomainException
{
    public static function becauseAlreadyVerified(string $id): self
    {
        return new self(\sprintf('Identity of user "%s" is already verified.', $id));
    }
}
