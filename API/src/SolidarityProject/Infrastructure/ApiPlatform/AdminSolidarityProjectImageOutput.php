<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminSolidarityProjectImage',
    operations: [
        new Post(
            uriTemplate: '/admin/solidarity-project-images',
            status: 201,
            openapi: new OpenApiOperation(
                summary: 'Téléverser une image de projet solidaire (administration)',
                description: 'Téléverse une image (JPEG, PNG ou WebP, 10 Mo maximum) et retourne son URL publique, à utiliser comme image d\'un projet solidaire. Réservé aux administrateurs (ROLE_ADMIN).',
            ),
            security: "is_granted('ROLE_ADMIN')",
            inputFormats: ['multipart' => ['multipart/form-data']],
            normalizationContext: ['groups' => ['admin_solidarity_project_image:read']],
            deserialize: false,
            input: false,
            processor: UploadSolidarityProjectImageProcessor::class,
        ),
    ],
)]
final class AdminSolidarityProjectImageOutput
{
    #[Groups(['admin_solidarity_project_image:read'])]
    #[ApiProperty(description: 'URL publique de l\'image téléversée', example: 'http://localhost:8080/uploads/solidarity-projects/0199....webp')]
    public ?string $imageUrl = null;
}
