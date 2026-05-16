<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Exception;

final class InvalidConversationIdException extends \DomainException
{
    public static function becauseNull(): self
    {
        return new self('Conversation id is required.');
    }
}
