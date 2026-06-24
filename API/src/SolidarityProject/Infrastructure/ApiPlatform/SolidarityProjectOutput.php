<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SolidarityProject',
    operations: [
        new Get(
            uriTemplate: '/solidarity_projects/{id}',
            openapi: new OpenApiOperation(
                summary: 'Récupérer un projet solidaire',
                description: 'Retourne le détail complet d\'un projet solidaire par son identifiant UUID. Le contenu (titre, description, chiffres clés) est servi dans la langue négociée via l\'en-tête Accept-Language (fr par défaut, en disponible).',
            ),
            normalizationContext: ['groups' => ['solidarity_project:read']],
            provider: SolidarityProjectItemProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/solidarity_projects',
            openapi: new OpenApiOperation(
                summary: 'Lister les projets solidaires actifs',
                description: 'Retourne la liste des projets solidaires actifs, triés par date de création décroissante. Le contenu est servi dans la langue négociée via l\'en-tête Accept-Language (fr par défaut, en disponible).',
            ),
            normalizationContext: ['groups' => ['solidarity_project:list']],
            provider: ActiveSolidarityProjectProvider::class,
            paginationEnabled: false,
        ),
        new Patch(
            uriTemplate: '/solidarity_projects/{id}/mark-default',
            status: 204,
            openapi: new OpenApiOperation(
                summary: 'Définir le projet solidaire par défaut de la plateforme',
                description: 'Marque ce projet comme le projet par défaut affiché sur les hébergements quand l\'équipe hôte n\'a pas de coup de cœur. Démarque automatiquement le projet précédemment marqué comme défaut. Action de curation réservée aux administrateurs de la plateforme (ROLE_ADMIN).',
            ),
            denormalizationContext: ['groups' => ['solidarity_project:mark-default']],
            security: "is_granted('ROLE_ADMIN')",
            input: false,
            output: false,
            processor: MarkSolidarityProjectAsDefaultProcessor::class,
            read: false,
        ),
    ],
    stateOptions: new Options(entityClass: SolidarityProjectEntity::class),
)]
class SolidarityProjectOutput implements FromEntityInterface
{
    #[Groups(['solidarity_project:read', 'solidarity_project:list'])]
    #[ApiProperty(description: 'Identifiant unique (UUID)', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['solidarity_project:read', 'solidarity_project:list'])]
    #[ApiProperty(description: 'Titre du projet solidaire', example: 'Reforestation de l\'île')]
    public ?string $title = null;

    #[Groups(['solidarity_project:read', 'solidarity_project:list'])]
    #[ApiProperty(description: 'Description détaillée du projet', example: 'Plantation de 10 000 arbres endémiques sur trois ans.')]
    public ?string $description = null;

    #[Groups(['solidarity_project:read', 'solidarity_project:list'])]
    #[ApiProperty(description: 'URL de l\'image du projet', example: 'https://example.com/images/project.jpg')]
    public ?string $imageUrl = null;

    #[Groups(['solidarity_project:read', 'solidarity_project:list'])]
    #[ApiProperty(description: 'Statut du projet (active ou closed)', example: 'active')]
    public ?string $status = null;

    #[Groups(['solidarity_project:read', 'solidarity_project:list'])]
    #[ApiProperty(description: 'Date de création du projet (ISO 8601)', example: '2026-04-13T10:00:00+00:00')]
    public ?string $createdAt = null;

    #[Groups(['solidarity_project:read', 'solidarity_project:list'])]
    #[ApiProperty(description: 'Vrai si ce projet est marqué comme défaut de la plateforme', example: false)]
    public ?bool $isDefault = null;

    #[Groups(['solidarity_project:read', 'solidarity_project:list'])]
    #[ApiProperty(description: 'Langue dans laquelle le contenu (titre, description, chiffres clés) est servi', example: 'fr')]
    public ?string $locale = null;

    /**
     * @var array<array{value: string, label: string}>
     */
    #[Groups(['solidarity_project:read'])]
    #[ApiProperty(
        description: 'Chiffres clés du projet (valeur + libellé), affichés en tête de la page projet',
        example: [['value' => '10 000', 'label' => 'arbres plantés'], ['value' => '3 ans', 'label' => 'de programme']],
        openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'value' => ['type' => 'string'],
                    'label' => ['type' => 'string'],
                ],
                'required' => ['value', 'label'],
            ],
        ],
    )]
    public array $keyFigures = [];

    public static function fromEntity(object $entity, string $locale = SolidarityProject::DEFAULT_LOCALE): static
    {
        /** @var SolidarityProjectEntity $entity */
        $translations = $entity->getTranslations();
        $translation = $translations[$locale]
            ?? $translations[SolidarityProject::DEFAULT_LOCALE]
            ?? ['title' => null, 'description' => null, 'keyFigures' => []];

        $output = new static();
        $output->id = $entity->getId()?->toRfc4122();
        $output->title = $translation['title'] ?? null;
        $output->description = $translation['description'] ?? null;
        $output->imageUrl = $entity->getImageUrl();
        $output->status = $entity->getStatus();
        $output->createdAt = $entity->getCreatedAt()?->format(\DateTimeInterface::ATOM);
        $output->isDefault = $entity->isDefault();
        $output->locale = isset($translations[$locale]) ? $locale : SolidarityProject::DEFAULT_LOCALE;
        $output->keyFigures = $translation['keyFigures'] ?? [];

        return $output;
    }
}
