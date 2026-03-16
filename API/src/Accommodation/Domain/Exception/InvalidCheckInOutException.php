<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidCheckInOutException extends \DomainException
{
    public static function becauseInvalidFormat(string $value): self
    {
        return new self(\sprintf('Le format d\'heure "%s" est invalide. Utilisez le format HH:MM.', $value));
    }
}
