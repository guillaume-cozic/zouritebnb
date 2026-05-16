<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Entity;

use App\Conversation\Domain\Exception\InvalidMessageException;

final readonly class MessageBody
{
    private const int MAX_LENGTH = 5000;

    public function __construct(private ?string $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->value) {
            throw InvalidMessageException::becauseBodyNull();
        }
        if ('' === trim($this->value)) {
            throw InvalidMessageException::becauseBodyEmpty();
        }
        if (mb_strlen($this->value) > self::MAX_LENGTH) {
            throw InvalidMessageException::becauseBodyTooLong(self::MAX_LENGTH);
        }
    }

    public function toString(): string
    {
        return $this->value;
    }
}
