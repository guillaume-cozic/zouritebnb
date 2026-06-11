<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationCheckInOutInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Heure d\'arrivée au format HH:MM', example: '16:00')]
        #[Assert\Regex(pattern: '/^\d{2}:\d{2}$/', message: 'L\'heure d\'arrivée doit être au format HH:MM.')]
        public ?string $checkIn = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Heure de départ au format HH:MM', example: '12:00')]
        #[Assert\Regex(pattern: '/^\d{2}:\d{2}$/', message: 'L\'heure de départ doit être au format HH:MM.')]
        public ?string $checkOut = null,
    ) {
    }
}
