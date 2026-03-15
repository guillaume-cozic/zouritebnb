<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationAddressInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Rue', example: '12 rue de la Paix')]
        public ?string $street = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Ville', example: 'Paris')]
        public ?string $city = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Code postal', example: '75002')]
        public ?string $zipCode = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Pays', example: 'France')]
        public ?string $country = null,
    ) {
    }
}
