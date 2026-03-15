<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidAddressException;

final readonly class Address
{
    public function __construct(
        private string $street,
        private string $city,
        private string $zipCode,
        private string $country,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ('' === trim($this->street)) {
            throw InvalidAddressException::becauseEmptyStreet();
        }

        if ('' === trim($this->city)) {
            throw InvalidAddressException::becauseEmptyCity();
        }

        if ('' === trim($this->country)) {
            throw InvalidAddressException::becauseEmptyCountry();
        }
    }

    public function street(): string
    {
        return $this->street;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function zipCode(): string
    {
        return $this->zipCode;
    }

    public function country(): string
    {
        return $this->country;
    }
}
