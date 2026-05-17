<?php

declare(strict_types=1);

namespace App\Geography\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Geography\Domain\Entity\Locality;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Locality',
    operations: [
        new GetCollection(
            uriTemplate: '/localities',
            openapi: new OpenApiOperation(
                summary: 'Lister les localités',
                description: 'Retourne la liste des localités, optionnellement filtrée par code de région via le paramètre `regionCode`.',
            ),
            normalizationContext: ['groups' => ['locality:list']],
            provider: LocalityCollectionProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
class LocalityOutput implements FromEntityInterface
{
    #[Groups(['locality:list'])]
    #[ApiProperty(description: 'Identifiant unique (UUID)', example: '01961e2f-dead-7000-beef-000000000001')]
    public ?string $id = null;

    #[Groups(['locality:list'])]
    #[ApiProperty(description: 'Nom de la localité', example: 'Port Mathurin')]
    public ?string $name = null;

    #[Groups(['locality:list'])]
    #[ApiProperty(description: 'Identifiant (UUID) de la région à laquelle la localité appartient', example: '00000000-0000-4000-8000-00000000000a')]
    public ?string $regionId = null;

    public static function fromEntity(object $entity): static
    {
        $output = new static();

        if ($entity instanceof Locality) {
            $output->id = $entity->getId()->toRfc4122();
            $output->name = $entity->getName();
            $output->regionId = $entity->getRegionId()->toRfc4122();
        }

        return $output;
    }
}
