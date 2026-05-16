<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Exception;

final class ConversationParticipantException extends \DomainException
{
    public static function becauseUserIsNotAllowed(string $userId): self
    {
        return new self(\sprintf('User "%s" is not allowed to access this conversation.', $userId));
    }
}
