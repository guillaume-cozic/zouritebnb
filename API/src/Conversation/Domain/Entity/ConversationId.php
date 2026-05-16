<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Entity;

use App\Conversation\Domain\Exception\InvalidConversationIdException;
use Symfony\Component\Uid\Uuid;

final readonly class ConversationId
{
    public function __construct(private ?Uuid $value)
    {
        if (null === $this->value) {
            throw InvalidConversationIdException::becauseNull();
        }
    }

    public function toUuid(): Uuid
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value->toRfc4122();
    }
}
