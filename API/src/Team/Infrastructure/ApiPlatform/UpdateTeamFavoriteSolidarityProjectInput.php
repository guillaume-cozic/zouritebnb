<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateTeamFavoriteSolidarityProjectInput
{
    public function __construct(
        #[Groups(['team:write'])]
        #[ApiProperty(description: 'Identifiant UUID du projet solidaire coup de cœur (ou null pour retirer)', example: '019cf27a-96ba-7957-8622-eeccb764b67f')]
        public ?string $favoriteSolidarityProjectId = null,
    ) {
    }
}
