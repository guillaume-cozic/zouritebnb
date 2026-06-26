<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ReservationInput
{
    public function __construct(
        #[Groups(['reservation:write'])]
        #[ApiProperty(description: 'Identifiant UUID de l\'hébergement réservé', example: '01961e2f-dead-7000-beef-000000000001')]
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $accommodationId = '',

        #[Groups(['reservation:write'])]
        #[ApiProperty(description: 'Date et heure d\'arrivée au format ISO 8601', example: '2026-05-01T15:00:00+00:00')]
        #[Assert\NotBlank]
        public string $checkIn = '',

        #[Groups(['reservation:write'])]
        #[ApiProperty(description: 'Date et heure de départ au format ISO 8601. Doit être strictement postérieure à checkIn.', example: '2026-05-05T11:00:00+00:00')]
        #[Assert\NotBlank]
        public string $checkOut = '',

        #[Groups(['reservation:write'])]
        #[ApiProperty(description: 'Nom du voyageur principal. Ne peut pas être vide.', example: 'Jean Dupont')]
        #[Assert\NotBlank(normalizer: 'trim')]
        #[Assert\Length(max: 255)]
        public string $guestName = '',

        #[Groups(['reservation:write'])]
        #[ApiProperty(description: 'Nombre de voyageurs. Doit être au moins 1 et ne pas dépasser la capacité d\'accueil de l\'hébergement (422 sinon).', example: 2)]
        #[Assert\Positive]
        public int $guestCount = 1,
    ) {
    }
}
