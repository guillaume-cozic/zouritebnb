<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ActivityPoint',
    operations: [
        new GetCollection(
            uriTemplate: '/activity-points',
            openapi: new OpenApiOperation(
                summary: 'Lister les points d\'activité de Rodrigues',
                description: 'Retourne la liste complète (non paginée) des points d\'activité de l\'île Rodrigues : spots de kitesurf, points de vue, sites naturels, plages, sites de plongée et patrimoine. Route publique, destinée à la carte interactive du site.',
            ),
            normalizationContext: ['groups' => ['activity_point:list'], 'skip_null_values' => false],
            provider: ActivityPointCollectionProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
final class ActivityPointOutput
{
    #[Groups(['activity_point:list'])]
    #[ApiProperty(description: 'Identifiant unique du point d\'activité (UUID)', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['activity_point:list'])]
    #[ApiProperty(description: 'Nom du point d\'activité', example: 'Lagune de Mourouk')]
    public ?string $name = null;

    #[Groups(['activity_point:list'])]
    #[ApiProperty(description: 'Description du point d\'activité', example: 'Spot de kitesurf réputé pour son lagon turquoise et son vent régulier.')]
    public ?string $description = null;

    #[Groups(['activity_point:list'])]
    #[ApiProperty(description: 'Catégorie du point (kitesurf, viewpoint, nature, beach, diving, heritage ou activity)', example: 'kitesurf')]
    public ?string $category = null;

    #[Groups(['activity_point:list'])]
    #[ApiProperty(description: 'Latitude du point (bornes Rodrigues : -20.05 à -19.35)', example: -19.7577)]
    public ?float $latitude = null;

    #[Groups(['activity_point:list'])]
    #[ApiProperty(description: 'Longitude du point (bornes Rodrigues : 62.95 à 63.95)', example: 63.4499)]
    public ?float $longitude = null;

    #[Groups(['activity_point:list'])]
    #[ApiProperty(description: 'URL d\'un article lié au point (null si aucun)', example: '/blog/kitesurf-mourouk')]
    public ?string $articleUrl = null;
}
