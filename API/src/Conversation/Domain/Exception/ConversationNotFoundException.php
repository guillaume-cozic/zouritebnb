<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Exception;

final class ConversationNotFoundException extends \DomainException
{
    public static function becauseId(string $id): self
    {
        return new self(\sprintf('Conversation "%s" not found.', $id));
    }

    public static function becauseReservationId(string $reservationId): self
    {
        return new self(\sprintf('No conversation found for reservation "%s".', $reservationId));
    }
}
