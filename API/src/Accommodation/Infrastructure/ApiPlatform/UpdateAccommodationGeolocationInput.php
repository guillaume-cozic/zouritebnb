<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationGeolocationInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Latitude', example: 48.8566)]
        #[Assert\Range(min: -90, max: 90)]
        public float $latitude = 0,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Longitude', example: 2.3522)]
        #[Assert\Range(min: -180, max: 180)]
        public float $longitude = 0,
    ) {
    }
}
