<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Exception;

final class CannotStartConversationException extends \DomainException
{
    public static function becauseReservationNotFound(string $reservationId): self
    {
        return new self(\sprintf('Cannot start conversation: reservation "%s" not found.', $reservationId));
    }

    public static function becauseReservationHasNoGuestUser(string $reservationId): self
    {
        return new self(\sprintf('Cannot start conversation: reservation "%s" has no guest user.', $reservationId));
    }
}
