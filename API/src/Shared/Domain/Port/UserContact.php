<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

final readonly class UserContact
{
    public function __construct(
        public Uuid $userId,
        public string $email,
        public ?string $firstName,
        public ?string $phoneNumber = null,
    ) {
    }

    public function hasPhone(): bool
    {
        return null !== $this->phoneNumber && '' !== trim($this->phoneNumber);
    }

    /**
     * A friendly greeting name, falling back to the local part of the email when
     * the user has not filled in their first name yet.
     */
    public function greetingName(): string
    {
        if (null !== $this->firstName && '' !== trim($this->firstName)) {
            return $this->firstName;
        }

        $localPart = strstr($this->email, '@', true);

        return false !== $localPart ? $localPart : $this->email;
    }
}
