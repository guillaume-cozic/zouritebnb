<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SubmitGuestReviewInput
{
    public function __construct(
        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Identifiant UUID de l\'hébergement concerné par le séjour terminé.', example: '01961e2f-dead-7000-beef-000000000001')]
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $accommodationId = '',

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Identifiant UUID du voyageur noté. Doit avoir effectué un séjour confirmé et terminé sur l\'hébergement.', example: '01961e2f-dead-7000-beef-0000000000c1')]
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $guestUserId = '',

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Note attribuée au voyageur, entier compris entre 1 et 5 inclus.', example: 5)]
        #[Assert\NotNull]
        #[Assert\Range(min: 1, max: 5)]
        public ?int $rating = null,

        #[Groups(['review:write'])]
        #[ApiProperty(description: 'Commentaire de l\'avis. Doit contenir au moins 50 caractères (hors espaces de début/fin).', example: 'Voyageur exemplaire : communication parfaite, logement laissé impeccable et respect total du règlement intérieur.')]
        #[Assert\NotNull]
        #[Assert\Length(min: 50, normalizer: 'trim')]
        public ?string $comment = null,
    ) {
    }
}
