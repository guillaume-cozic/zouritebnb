<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationGeolocationInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Latitude', example: 48.8566)]
        public float $latitude = 0,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Longitude', example: 2.3522)]
        public float $longitude = 0,
    ) {
    }
}
