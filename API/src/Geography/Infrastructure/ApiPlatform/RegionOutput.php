<?php

declare(strict_types=1);

namespace App\Geography\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use App\Geography\Domain\Entity\Region;
use App\Shared\ApiPlatform\State\FromEntityInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Region',
    operations: [
        new GetCollection(
            uriTemplate: '/regions',
            openapi: new OpenApiOperation(
                summary: 'Lister les régions',
                description: 'Retourne la liste complète des régions disponibles, triées par nom.',
            ),
            normalizationContext: ['groups' => ['region:list']],
            provider: RegionCollectionProvider::class,
            paginationEnabled: false,
        ),
    ],
)]
class RegionOutput implements FromEntityInterface
{
    #[Groups(['region:list'])]
    #[ApiProperty(description: 'Identifiant unique (UUID)', example: '00000000-0000-4000-8000-00000000000a')]
    public ?string $id = null;

    #[Groups(['region:list'])]
    #[ApiProperty(description: 'Code court et stable de la région', example: 'RODRIGUES')]
    public ?string $code = null;

    #[Groups(['region:list'])]
    #[ApiProperty(description: 'Nom affiché de la région', example: 'Rodrigues')]
    public ?string $name = null;

    public static function fromEntity(object $entity): static
    {
        $output = new static();

        if ($entity instanceof Region) {
            $output->id = $entity->getId()->toRfc4122();
            $output->code = $entity->getCode();
            $output->name = $entity->getName();
        }

        return $output;
    }
}
