<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminReview',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/reviews',
            openapi: new OpenApiOperation(
                summary: 'Lister tous les avis (administration)',
                description: 'Retourne la liste complète des avis de la plateforme (avis sur hébergements et avis sur voyageurs), du plus récent au plus ancien, avec les noms de l\'auteur et du sujet de l\'avis. Endpoint en lecture seule réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_review:list'], 'skip_null_values' => false],
            provider: AdminReviewCollectionProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class AdminReviewOutput
{
    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Identifiant unique de l\'avis (UUID)', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Type d\'avis (accommodation ou guest)', example: 'accommodation')]
    public ?string $type = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Note attribuée sur 5', example: 5)]
    public ?int $rating = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Commentaire de l\'avis', example: 'Séjour parfait, logement propre et bien situé, hôte très réactif.')]
    public ?string $comment = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Date de publication de l\'avis (ISO 8601)', example: '2026-05-12T14:30:00+00:00')]
    public ?string $createdAt = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'auteur de l\'avis', example: '01961e2f-dead-7000-beef-0000000000c1')]
    public ?string $authorUserId = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Nom complet de l\'auteur, ou son email à défaut (null si le compte a été supprimé)', example: 'Marie Dupont')]
    public ?string $authorName = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'hébergement noté (null pour un avis sur voyageur)', example: '01961e2f-dead-7000-beef-0000000000a1')]
    public ?string $subjectAccommodationId = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Titre de l\'hébergement noté (null pour un avis sur voyageur)', example: 'Villa avec vue sur le lagon')]
    public ?string $subjectAccommodationTitle = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Identifiant UUID du voyageur noté (null pour un avis sur hébergement)', example: '01961e2f-dead-7000-beef-0000000000c2')]
    public ?string $subjectUserId = null;

    #[Groups(['admin_review:list'])]
    #[ApiProperty(description: 'Nom complet du voyageur noté, ou son email à défaut (null pour un avis sur hébergement)', example: 'Paul Martin')]
    public ?string $subjectUserName = null;
}
