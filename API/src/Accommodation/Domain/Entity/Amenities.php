<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidAmenitiesException;

final readonly class Amenities
{
    /**
     * @param array<string> $codes
     */
    public function __construct(
        private array $codes,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        foreach ($this->codes as $code) {
            if (!\is_string($code)) {
                throw InvalidAmenitiesException::becauseInvalidCode($code);
            }

            if ('' === trim($code)) {
                throw InvalidAmenitiesException::becauseEmptyCode();
            }
        }
    }

    /**
     * @return array<string>
     */
    public function codes(): array
    {
        return $this->codes;
    }
}
