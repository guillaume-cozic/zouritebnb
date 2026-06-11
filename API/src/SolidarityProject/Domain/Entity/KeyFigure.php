<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Entity;

use App\SolidarityProject\Domain\Exception\InvalidKeyFigureException;

final readonly class KeyFigure
{
    public function __construct(
        private ?string $value,
        private ?string $label,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->value || '' === trim($this->value)) {
            throw InvalidKeyFigureException::becauseValueIsBlank();
        }

        if (null === $this->label || '' === trim($this->label)) {
            throw InvalidKeyFigureException::becauseLabelIsBlank();
        }
    }

    public function value(): string
    {
        return trim($this->value);
    }

    public function label(): string
    {
        return trim($this->label);
    }
}
