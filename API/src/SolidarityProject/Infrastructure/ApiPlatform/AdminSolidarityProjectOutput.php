<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminSolidarityProject',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/solidarity-projects',
            openapi: new OpenApiOperation(
                summary: 'Lister tous les projets solidaires (administration)',
                description: 'Retourne la liste complète des projets solidaires de la plateforme (actifs et clôturés), le projet coup de cœur en premier puis du plus récent au plus ancien. Réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_solidarity_project:list'], 'skip_null_values' => false],
            provider: AdminSolidarityProjectCollectionProvider::class,
            paginationEnabled: true,
            paginationItemsPerPage: 20,
            paginationClientItemsPerPage: true,
            paginationMaximumItemsPerPage: 100,
        ),
        new Get(
            uriTemplate: '/admin/solidarity-projects/{id}',
            openapi: new OpenApiOperation(
                summary: 'Récupérer un projet solidaire (administration)',
                description: 'Retourne le détail complet d\'un projet solidaire, quel que soit son statut. Réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_solidarity_project:list'], 'skip_null_values' => false],
            provider: AdminSolidarityProjectItemProvider::class,
        ),
        new Post(
            uriTemplate: '/admin/solidarity-projects',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Créer un projet solidaire (administration)',
                description: 'Crée un nouveau projet solidaire. Le statut par défaut est "active". Réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['admin_solidarity_project:list'], 'skip_null_values' => false],
            denormalizationContext: ['groups' => ['admin_solidarity_project:write']],
            input: AdminSolidarityProjectInput::class,
            processor: CreateSolidarityProjectProcessor::class,
        ),
        new Patch(
            uriTemplate: '/admin/solidarity-projects/{id}',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Modifier un projet solidaire (administration)',
                description: 'Met à jour les informations d\'un projet solidaire (titre, description, image, statut, chiffres clés). Le coup de cœur de la plateforme et la date de création sont préservés. Réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['admin_solidarity_project:write']],
            input: AdminSolidarityProjectInput::class,
            output: false,
            processor: UpdateSolidarityProjectProcessor::class,
            read: false,
        ),
        new Patch(
            uriTemplate: '/admin/solidarity-projects/{id}/status',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Activer ou désactiver un projet solidaire (administration)',
                description: 'Change le statut d\'un projet solidaire ("active" pour l\'afficher publiquement, "closed" pour le retirer). Réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['admin_solidarity_project:status']],
            input: AdminSolidarityProjectStatusInput::class,
            output: false,
            processor: SetSolidarityProjectStatusProcessor::class,
            read: false,
        ),
    ],
)]
final class AdminSolidarityProjectOutput
{
    #[Groups(['admin_solidarity_project:list'])]
    #[ApiProperty(description: 'Identifiant unique du projet (UUID)', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['admin_solidarity_project:list'])]
    #[ApiProperty(description: 'Titre du projet dans la langue par défaut (fr)', example: 'Reforestation de l\'île Rodrigues')]
    public ?string $title = null;

    #[Groups(['admin_solidarity_project:list'])]
    #[ApiProperty(description: 'Description du projet dans la langue par défaut (fr)', example: 'Plantation de 10 000 arbres endémiques sur trois ans.')]
    public ?string $description = null;

    #[Groups(['admin_solidarity_project:list'])]
    #[ApiProperty(description: 'URL de l\'image du projet (null si aucune)', example: 'https://example.com/images/project.jpg')]
    public ?string $imageUrl = null;

    #[Groups(['admin_solidarity_project:list'])]
    #[ApiProperty(description: 'Statut du projet (active ou closed)', example: 'active')]
    public ?string $status = null;

    #[Groups(['admin_solidarity_project:list'])]
    #[ApiProperty(description: 'Date de création (ISO 8601)', example: '2026-04-13T10:00:00+00:00')]
    public ?string $createdAt = null;

    #[Groups(['admin_solidarity_project:list'])]
    #[ApiProperty(description: 'Vrai si ce projet est le coup de cœur (projet par défaut) de la plateforme', example: false)]
    public ?bool $isDefault = null;

    /**
     * @var array<array{value: string, label: string}>
     */
    #[Groups(['admin_solidarity_project:list'])]
    #[ApiProperty(
        description: 'Chiffres clés du projet dans la langue par défaut (fr)',
        example: [['value' => '10 000', 'label' => 'arbres plantés']],
    )]
    public array $keyFigures = [];

    /**
     * @var array<string, array{title: string, description: string, keyFigures: array<array{value: string, label: string}>}>
     */
    #[Groups(['admin_solidarity_project:list'])]
    #[ApiProperty(
        description: 'Contenu traduisible par langue (fr requis, en optionnel). Sert à éditer chaque langue dans le back-office.',
        example: [
            'fr' => ['title' => 'Reforestation de l\'île Rodrigues', 'description' => 'Plantation de 10 000 arbres endémiques.', 'keyFigures' => [['value' => '10 000', 'label' => 'arbres plantés']]],
            'en' => ['title' => 'Reforesting Rodrigues', 'description' => 'Planting 10,000 endemic trees.', 'keyFigures' => [['value' => '10,000', 'label' => 'trees planted']]],
        ],
    )]
    public array $translations = [];
}
