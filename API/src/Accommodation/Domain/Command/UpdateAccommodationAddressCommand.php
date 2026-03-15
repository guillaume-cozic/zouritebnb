<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationAddressCommand
{
    public function __construct(
        public Uuid $id,
        public ?string $street,
        public ?string $city,
        public ?string $zipCode,
        public ?string $country,
    ) {
    }
}
