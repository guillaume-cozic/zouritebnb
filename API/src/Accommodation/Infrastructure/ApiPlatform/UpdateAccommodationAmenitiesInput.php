<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationAmenitiesInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Liste des codes d\'équipements', example: ['private_pool', 'wifi', 'parking'])]
        public ?array $codes = null,
    ) {
    }
}
