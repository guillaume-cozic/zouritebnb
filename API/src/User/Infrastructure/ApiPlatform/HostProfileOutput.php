<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'HostProfile',
    operations: [
        new Get(
            uriTemplate: '/host-profiles/{teamId}',
            openapi: new OpenApiOperation(
                summary: 'Profil public de l\'hôte d\'une équipe',
                description: 'Retourne le profil public de l\'hôte (prénom, nom, photo, présentation) à partir de l\'identifiant de son équipe. Accessible sans authentification : utilisé sur la page publique d\'une annonce et dans la messagerie. 404 si l\'équipe n\'a aucun hôte.',
            ),
            normalizationContext: ['groups' => ['host_profile:read']],
            provider: HostProfileProvider::class,
        ),
    ],
)]
class HostProfileOutput
{
    #[Groups(['host_profile:read'])]
    #[ApiProperty(description: 'Identifiant UUID de l\'équipe hôte')]
    public ?string $teamId = null;

    #[Groups(['host_profile:read'])]
    #[ApiProperty(description: 'Prénom de l\'hôte', example: 'Marie')]
    public ?string $firstName = null;

    #[Groups(['host_profile:read'])]
    #[ApiProperty(description: 'Nom de l\'hôte', example: 'Dupont')]
    public ?string $lastName = null;

    #[Groups(['host_profile:read'])]
    #[ApiProperty(description: 'Présentation publique de l\'hôte', example: 'Passionnée de randonnée, je loue mon gîte familial depuis 2015.')]
    public ?string $bio = null;

    #[Groups(['host_profile:read'])]
    #[ApiProperty(description: 'URL (relative) de la photo de l\'hôte, ou null', example: '/uploads/photos/019cf27a-96ba-7957-8622-eeccb7350e79.jpg')]
    public ?string $avatarUrl = null;
}
