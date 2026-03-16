<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationCheckInOutInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Heure d\'arrivée au format HH:MM', example: '16:00')]
        public ?string $checkIn = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Heure de départ au format HH:MM', example: '12:00')]
        public ?string $checkOut = null,
    ) {
    }
}
