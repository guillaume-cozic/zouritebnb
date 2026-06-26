<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class InvalidEmailVerificationTokenException extends \DomainException
{
    public static function becauseInvalidOrExpired(): self
    {
        return new self('Ce lien de vérification est invalide ou a expiré. Merci d\'en demander un nouveau.');
    }
}
