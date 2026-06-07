<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class SubmitGuestReviewInput
{
    public function __construct(
        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Identifiant UUID du loueur qui rédige l\'avis (utilisateur courant authentifié). Doit être membre de l\'équipe hôte de l\'hébergement.', example: '01961e2f-dead-7000-beef-0000000000a1')]
        public string $authorUserId = '',

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Identifiant UUID de l\'hébergement concerné par le séjour terminé.', example: '01961e2f-dead-7000-beef-000000000001')]
        public string $accommodationId = '',

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Identifiant UUID du voyageur noté. Doit avoir effectué un séjour confirmé et terminé sur l\'hébergement.', example: '01961e2f-dead-7000-beef-0000000000c1')]
        public string $guestUserId = '',

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Note attribuée au voyageur, entier compris entre 1 et 5 inclus.', example: 5)]
        public ?int $rating = null,

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Commentaire de l\'avis. Doit contenir au moins 50 caractères (hors espaces de début/fin).', example: 'Voyageur exemplaire : communication parfaite, logement laissé impeccable et respect total du règlement intérieur.')]
        public ?string $comment = null,
    ) {
    }
}
