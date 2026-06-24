<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

class HostAvatarOutput
{
    #[Groups(['user:read'])]
    #[ApiProperty(description: 'URL (relative) de la photo de l\'hôte qui vient d\'être téléversée', example: '/uploads/photos/019cf27a-96ba-7957-8622-eeccb7350e79.jpg')]
    public ?string $avatarUrl = null;
}
