<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Entity;

use App\Conversation\Domain\Exception\InvalidMessageException;
use Symfony\Component\Uid\Uuid;

final readonly class MessageId
{
    public function __construct(private ?Uuid $value)
    {
        if (null === $this->value) {
            throw InvalidMessageException::becauseIdNull();
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
