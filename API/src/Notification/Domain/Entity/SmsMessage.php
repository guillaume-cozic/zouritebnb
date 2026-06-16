<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

final readonly class SmsMessage
{
    public function __construct(
        private PhoneNumber $recipient,
        private string $text,
    ) {
    }

    public function getRecipient(): PhoneNumber
    {
        return $this->recipient;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
