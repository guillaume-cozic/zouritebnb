<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RequestReservationModificationInput
{
    public function __construct(
        #[Groups(['reservation:write'])]
        #[ApiProperty(description: 'Nouvelle date et heure d\'arrivée souhaitée (ISO 8601).', example: '2026-07-10T15:00:00+00:00')]
        #[Assert\NotNull]
        public ?string $checkIn = null,

        #[Groups(['reservation:write'])]
        #[ApiProperty(description: 'Nouvelle date et heure de départ souhaitée (ISO 8601).', example: '2026-07-14T11:00:00+00:00')]
        #[Assert\NotNull]
        public ?string $checkOut = null,
    ) {
    }
}
