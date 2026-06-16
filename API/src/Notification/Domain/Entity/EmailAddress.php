<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use App\Notification\Domain\Exception\InvalidEmailAddressException;

final readonly class EmailAddress
{
    public function __construct(private ?string $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->value || '' === trim($this->value)) {
            throw InvalidEmailAddressException::becauseNull();
        }

        if (false === filter_var($this->value, \FILTER_VALIDATE_EMAIL)) {
            throw InvalidEmailAddressException::becauseInvalidFormat($this->value);
        }
    }

    public function toString(): string
    {
        return $this->value;
    }
}
