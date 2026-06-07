<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class SubmitAccommodationReviewInput
{
    public function __construct(
        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Identifiant UUID du voyageur qui rédige l\'avis (utilisateur courant authentifié). Doit correspondre à un séjour confirmé et terminé sur l\'hébergement.', example: '01961e2f-dead-7000-beef-0000000000c1')]
        public string $authorUserId = '',

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Identifiant UUID de l\'hébergement noté.', example: '01961e2f-dead-7000-beef-000000000001')]
        public string $accommodationId = '',

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Note attribuée à l\'hébergement, entier compris entre 1 et 5 inclus.', example: 5)]
        public ?int $rating = null,

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Commentaire de l\'avis. Doit contenir au moins 50 caractères (hors espaces de début/fin).', example: 'Séjour vraiment agréable, logement propre, bien situé et hôte très réactif. Je recommande sans hésiter.')]
        public ?string $comment = null,
    ) {
    }
}
