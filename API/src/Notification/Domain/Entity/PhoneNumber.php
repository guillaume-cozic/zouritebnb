<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use App\Notification\Domain\Exception\InvalidPhoneNumberException;

final readonly class PhoneNumber
{
    public function __construct(private ?string $value)
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->value || '' === trim($this->value)) {
            throw InvalidPhoneNumberException::becauseNull();
        }

        // Lenient validation: optional leading +, then 6–15 digits (separators allowed).
        $digits = preg_replace('/[\s.\-()]/', '', $this->value);

        if (null === $digits || 1 !== preg_match('/^\+?[0-9]{6,15}$/', $digits)) {
            throw InvalidPhoneNumberException::becauseInvalidFormat($this->value);
        }
    }

    public function toString(): string
    {
        return trim($this->value);
    }
}
