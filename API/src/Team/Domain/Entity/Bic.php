<?php

declare(strict_types=1);

namespace App\Team\Domain\Entity;

use App\Team\Domain\Exception\InvalidBankAccountException;

final readonly class Bic
{
    private string $normalized;

    public function __construct(private ?string $value)
    {
        $this->normalized = $this->validateAndNormalize();
    }

    private function validateAndNormalize(): string
    {
        if (null === $this->value) {
            throw InvalidBankAccountException::becauseBicFormatInvalid('');
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $this->value) ?? '');

        if (1 !== preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $normalized)) {
            throw InvalidBankAccountException::becauseBicFormatInvalid($this->value);
        }

        return $normalized;
    }

    public function value(): string
    {
        return $this->normalized;
    }
}
