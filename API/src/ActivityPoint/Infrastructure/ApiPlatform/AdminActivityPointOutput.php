<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Example;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminActivityPoint',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/activity-points',
            openapi: new OpenApiOperation(
                summary: 'Lister les points d\'activité (administration)',
                description: 'Retourne la liste paginée des points d\'activité de la plateforme, triés par nom. Filtres optionnels : "search" (recherche partielle sur le nom et la description) et "category" (catégorie exacte). Réservé aux administrateurs (ROLE_ADMIN).',
                parameters: [
                    new Parameter(
                        name: 'search',
                        in: 'query',
                        description: 'Recherche partielle sur le nom ou la description',
                        required: false,
                        schema: ['type' => 'string'],
                        example: 'lagon',
                    ),
                    new Parameter(
                        name: 'category',
                        in: 'query',
                        description: 'Filtre sur la catégorie exacte (kitesurf, viewpoint, nature, beach, diving, heritage ou activity)',
                        required: false,
                        schema: ['type' => 'string', 'enum' => ['kitesurf', 'viewpoint', 'nature', 'beach', 'diving', 'heritage', 'activity']],
                        example: 'kitesurf',
                    ),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_activity_point:list'], 'skip_null_values' => false],
            provider: AdminActivityPointCollectionProvider::class,
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationClientItemsPerPage: true,
            paginationMaximumItemsPerPage: 100,
        ),
        new Get(
            uriTemplate: '/admin/activity-points/{id}',
            openapi: new OpenApiOperation(
                summary: 'Récupérer un point d\'activité (administration)',
                description: 'Retourne le détail complet d\'un point d\'activité par son identifiant UUID, pour pré-remplir le formulaire d\'édition. Répond 404 si le point est inconnu. Réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_activity_point:list'], 'skip_null_values' => false],
            provider: AdminActivityPointItemProvider::class,
        ),
        new Post(
            uriTemplate: '/admin/activity-points',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Créer un point d\'activité (administration)',
                description: 'Crée un nouveau point d\'activité sur la carte de Rodrigues. Le nom et la description sont obligatoires, la catégorie doit être l\'une de kitesurf, viewpoint, nature, beach, diving, heritage ou activity, et les coordonnées doivent rester dans les bornes de l\'île (latitude -20.05 à -19.35, longitude 62.95 à 63.95). Toute violation répond 422. Réservé aux administrateurs (ROLE_ADMIN).',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: [
                                        'name' => 'Lagune de Mourouk',
                                        'description' => 'Spot de kitesurf réputé pour son lagon turquoise et son vent régulier.',
                                        'category' => 'kitesurf',
                                        'latitude' => -19.7577,
                                        'longitude' => 63.4499,
                                        'articleUrl' => '/blog/kitesurf-mourouk',
                                    ],
                                ),
                                'blank_name' => new Example(
                                    summary: 'Invalide : nom vide',
                                    description: 'Répond 422 : le nom du point est obligatoire.',
                                    value: [
                                        'name' => '',
                                        'description' => 'Une description.',
                                        'category' => 'kitesurf',
                                        'latitude' => -19.7577,
                                        'longitude' => 63.4499,
                                    ],
                                ),
                                'invalid_category' => new Example(
                                    summary: 'Invalide : catégorie inconnue',
                                    description: 'Répond 422 : la catégorie doit être kitesurf, viewpoint, nature, beach, diving, heritage ou activity.',
                                    value: [
                                        'name' => 'Lagune de Mourouk',
                                        'description' => 'Une description.',
                                        'category' => 'surf',
                                        'latitude' => -19.7577,
                                        'longitude' => 63.4499,
                                    ],
                                ),
                                'latitude_out_of_bounds' => new Example(
                                    summary: 'Invalide : latitude hors de Rodrigues',
                                    description: 'Répond 422 : la latitude doit être comprise entre -20.05 et -19.35.',
                                    value: [
                                        'name' => 'Lagune de Mourouk',
                                        'description' => 'Une description.',
                                        'category' => 'kitesurf',
                                        'latitude' => -21.5,
                                        'longitude' => 63.4499,
                                    ],
                                ),
                                'missing_longitude' => new Example(
                                    summary: 'Invalide : longitude manquante',
                                    description: 'Répond 422 : la longitude est obligatoire.',
                                    value: [
                                        'name' => 'Lagune de Mourouk',
                                        'description' => 'Une description.',
                                        'category' => 'kitesurf',
                                        'latitude' => -19.7577,
                                    ],
                                ),
                                'invalid_article_url' => new Example(
                                    summary: 'Invalide : URL d\'article mal formée',
                                    description: 'Répond 422 : l\'URL d\'article doit commencer par "http://", "https://" ou "/".',
                                    value: [
                                        'name' => 'Lagune de Mourouk',
                                        'description' => 'Une description.',
                                        'category' => 'kitesurf',
                                        'latitude' => -19.7577,
                                        'longitude' => 63.4499,
                                        'articleUrl' => 'blog/kitesurf-mourouk',
                                    ],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['admin_activity_point:write']],
            input: AdminActivityPointInput::class,
            output: false,
            processor: CreateActivityPointProcessor::class,
        ),
        new Patch(
            uriTemplate: '/admin/activity-points/{id}',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Modifier un point d\'activité (administration)',
                description: 'Remplace les informations d\'un point d\'activité (nom, description, catégorie, coordonnées, URL d\'article). Les mêmes règles de validation qu\'à la création s\'appliquent (422 en cas de violation). Répond 404 si le point est inconnu. Réservé aux administrateurs (ROLE_ADMIN).',
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/merge-patch+json' => new MediaType(
                            examples: new \ArrayObject([
                                'valid' => new Example(
                                    summary: 'Requête valide',
                                    value: [
                                        'name' => 'Lagune de Mourouk',
                                        'description' => 'Spot de kitesurf réputé, vent régulier toute l\'année.',
                                        'category' => 'kitesurf',
                                        'latitude' => -19.7577,
                                        'longitude' => 63.4499,
                                        'articleUrl' => null,
                                    ],
                                ),
                                'blank_description' => new Example(
                                    summary: 'Invalide : description vide',
                                    description: 'Répond 422 : la description du point est obligatoire.',
                                    value: [
                                        'name' => 'Lagune de Mourouk',
                                        'description' => '',
                                        'category' => 'kitesurf',
                                        'latitude' => -19.7577,
                                        'longitude' => 63.4499,
                                    ],
                                ),
                                'longitude_out_of_bounds' => new Example(
                                    summary: 'Invalide : longitude hors de Rodrigues',
                                    description: 'Répond 422 : la longitude doit être comprise entre 62.95 et 63.95.',
                                    value: [
                                        'name' => 'Lagune de Mourouk',
                                        'description' => 'Une description.',
                                        'category' => 'kitesurf',
                                        'latitude' => -19.7577,
                                        'longitude' => 57.5,
                                    ],
                                ),
                            ]),
                        ),
                    ]),
                ),
            ),
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['admin_activity_point:write']],
            input: AdminActivityPointInput::class,
            output: false,
            processor: UpdateActivityPointProcessor::class,
            read: false,
        ),
        new Delete(
            uriTemplate: '/admin/activity-points/{id}',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Supprimer un point d\'activité (administration)',
                description: 'Supprime définitivement un point d\'activité de la carte. Répond 404 si le point est inconnu. Réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            output: false,
            processor: DeleteActivityPointProcessor::class,
            read: false,
        ),
    ],
)]
final class AdminActivityPointOutput
{
    #[Groups(['admin_activity_point:list'])]
    #[ApiProperty(description: 'Identifiant unique du point d\'activité (UUID)', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['admin_activity_point:list'])]
    #[ApiProperty(description: 'Nom du point d\'activité', example: 'Lagune de Mourouk')]
    public ?string $name = null;

    #[Groups(['admin_activity_point:list'])]
    #[ApiProperty(description: 'Description du point d\'activité', example: 'Spot de kitesurf réputé pour son lagon turquoise et son vent régulier.')]
    public ?string $description = null;

    #[Groups(['admin_activity_point:list'])]
    #[ApiProperty(description: 'Catégorie du point (kitesurf, viewpoint, nature, beach, diving, heritage ou activity)', example: 'kitesurf')]
    public ?string $category = null;

    #[Groups(['admin_activity_point:list'])]
    #[ApiProperty(description: 'Latitude du point (bornes Rodrigues : -20.05 à -19.35)', example: -19.7577)]
    public ?float $latitude = null;

    #[Groups(['admin_activity_point:list'])]
    #[ApiProperty(description: 'Longitude du point (bornes Rodrigues : 62.95 à 63.95)', example: 63.4499)]
    public ?float $longitude = null;

    #[Groups(['admin_activity_point:list'])]
    #[ApiProperty(description: 'URL d\'un article lié au point (null si aucun)', example: '/blog/kitesurf-mourouk')]
    public ?string $articleUrl = null;
}
