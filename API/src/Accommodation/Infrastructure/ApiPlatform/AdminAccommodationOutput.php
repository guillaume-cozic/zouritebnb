<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminAccommodation',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/accommodations',
            openapi: new OpenApiOperation(
                summary: 'Lister tous les hébergements (administration)',
                description: 'Retourne la liste complète des hébergements de la plateforme (brouillons inclus), triés par titre, avec l\'email d\'un membre de l\'équipe hôte. Endpoint en lecture seule réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_accommodation:list'], 'skip_null_values' => false],
            provider: AdminAccommodationCollectionProvider::class,
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationClientItemsPerPage: true,
            paginationMaximumItemsPerPage: 100,
        ),
    ],
)]
final class AdminAccommodationOutput
{
    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Identifiant unique de l\'hébergement (UUID)', example: '01961e2f-dead-7000-beef-0000000000a1')]
    public ?string $id = null;

    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Titre de l\'hébergement', example: 'Villa avec vue sur le lagon')]
    public ?string $title = null;

    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Statut de publication (draft ou published)', example: 'published')]
    public ?string $status = null;

    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Prix par nuit en euros', example: 120.0)]
    public ?float $price = null;

    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Ville de l\'hébergement', example: 'Papeete')]
    public ?string $city = null;

    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Nombre de chambres', example: 3)]
    public ?int $bedrooms = null;

    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Capacité maximale en voyageurs', example: 6)]
    public ?int $maxGuests = null;

    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Pourcentage de remise à la semaine (null si aucune promotion)', example: 15.0)]
    public ?float $weeklyPromotionPercentage = null;

    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'équipe hôte propriétaire', example: '01961e2f-dead-7000-beef-0000000000b1')]
    public ?string $teamId = null;

    #[Groups(['admin_accommodation:list'])]
    #[ApiProperty(description: 'Email d\'un membre de l\'équipe hôte (null si aucune équipe ou aucun membre)', example: 'host@example.com')]
    public ?string $hostEmail = null;
}
