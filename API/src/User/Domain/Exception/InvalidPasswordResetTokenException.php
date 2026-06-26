<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class InvalidPasswordResetTokenException extends \DomainException
{
    public static function becauseInvalidOrExpired(): self
    {
        return new self('Ce lien de réinitialisation est invalide ou a expiré. Merci d\'en demander un nouveau.');
    }
}
