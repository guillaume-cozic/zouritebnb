<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationCapacityInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre de chambres', example: 3)]
        public ?int $bedrooms = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre de salles de bain', example: 2)]
        public ?int $bathrooms = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre maximum de voyageurs', example: 6)]
        public ?int $maxGuests = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre de lits simples', example: 2)]
        public ?int $singleBeds = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre de lits doubles', example: 2)]
        public ?int $doubleBeds = null,
    ) {
    }
}
