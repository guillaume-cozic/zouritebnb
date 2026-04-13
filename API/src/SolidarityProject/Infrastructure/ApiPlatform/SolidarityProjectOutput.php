<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Shared\ApiPlatform\State\EntityProvider;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SolidarityProject',
    operations: [
        new Get(
            uriTemplate: '/solidarity_projects/{id}',
            openapi: new OpenApiOperation(
                summary: 'Récupérer un projet solidaire',
                description: 'Retourne le détail complet d\'un projet solidaire par son identifiant UUID.',
            ),
            normalizationContext: ['groups' => ['solidarity_project:read']],
            provider: EntityProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/solidarity_projects',
            openapi: new OpenApiOperation(
                summary: 'Lister les projets solidaires actifs',
                description: 'Retourne la liste des projets solidaires actifs, triés par date de création décroissante.',
            ),
            normalizationContext: ['groups' => ['solidarity_project:list']],
            provider: ActiveSolidarityProjectProvider::class,
            paginationEnabled: false,
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

    public static function fromEntity(object $entity): static
    {
        /** @var SolidarityProjectEntity $entity */
        $output = new static();
        $output->id = $entity->getId()?->toRfc4122();
        $output->title = $entity->getTitle();
        $output->description = $entity->getDescription();
        $output->imageUrl = $entity->getImageUrl();
        $output->status = $entity->getStatus();
        $output->createdAt = $entity->getCreatedAt()?->format(\DateTimeInterface::ATOM);

        return $output;
    }
}
