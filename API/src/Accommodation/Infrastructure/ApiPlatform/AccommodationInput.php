<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class AccommodationInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nom de l\'hébergement', example: 'Chalet Montagne')]
        public string $title = '',

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Description détaillée de l\'hébergement', example: 'Un chalet chaleureux au pied des pistes...')]
        public string $description = '',

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Prix par nuit en euros, doit être strictement positif', example: 150.0)]
        public ?float $price = null,
    ) {
    }
}
