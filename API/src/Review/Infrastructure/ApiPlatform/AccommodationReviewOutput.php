<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AccommodationReview',
    operations: [
        new GetCollection(
            uriTemplate: '/accommodations/{accommodationId}/reviews',
            uriVariables: ['accommodationId'],
            openapi: new OpenApiOperation(
                summary: 'Lister les avis d\'un hébergement',
                description: 'Retourne les avis laissés par les voyageurs sur un hébergement, du plus récent au plus ancien. Endpoint public.',
            ),
            normalizationContext: ['groups' => ['accommodation_review:read']],
            provider: AccommodationReviewCollectionProvider::class,
        ),
    ],
)]
class AccommodationReviewOutput
{
    #[Groups(['accommodation_review:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'avis', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['accommodation_review:read'])]
    #[ApiProperty(description: 'Note attribuée sur 5', example: 5)]
    public ?int $rating = null;

    #[Groups(['accommodation_review:read'])]
    #[ApiProperty(description: 'Commentaire du voyageur', example: 'Séjour parfait, logement propre et bien situé.')]
    public ?string $comment = null;

    #[Groups(['accommodation_review:read'])]
    #[ApiProperty(description: 'Nom affiché de l\'auteur (prénom + initiale du nom)', example: 'Marie D.')]
    public ?string $authorName = null;

    #[Groups(['accommodation_review:read'])]
    #[ApiProperty(description: 'URL relative de la photo de l\'auteur (à préfixer par l\'URL de l\'API), ou null', example: '/uploads/photos/avatar.jpg')]
    public ?string $authorAvatarUrl = null;

    #[Groups(['accommodation_review:read'])]
    #[ApiProperty(description: 'Date de publication de l\'avis (ISO 8601)', example: '2026-05-12T14:30:00+00:00')]
    public ?string $createdAt = null;
}
