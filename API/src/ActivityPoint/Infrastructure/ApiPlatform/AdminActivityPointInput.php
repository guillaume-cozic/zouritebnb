<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class AdminActivityPointInput
{
    public function __construct(
        #[Groups(['admin_activity_point:write'])]
        #[ApiProperty(description: 'Nom du point d\'activité (obligatoire, non vide)', example: 'Lagune de Mourouk')]
        public string $name = '',

        #[Groups(['admin_activity_point:write'])]
        #[ApiProperty(description: 'Description du point d\'activité (obligatoire, non vide)', example: 'Spot de kitesurf réputé pour son lagon turquoise et son vent régulier.')]
        public string $description = '',

        #[Groups(['admin_activity_point:write'])]
        #[ApiProperty(description: 'Catégorie du point : kitesurf, viewpoint, nature, beach, diving, heritage ou activity', example: 'kitesurf')]
        public string $category = '',

        #[Groups(['admin_activity_point:write'])]
        #[ApiProperty(description: 'Latitude du point, obligatoire, dans les bornes de Rodrigues (-20.05 à -19.35)', example: -19.7577)]
        public ?float $latitude = null,

        #[Groups(['admin_activity_point:write'])]
        #[ApiProperty(description: 'Longitude du point, obligatoire, dans les bornes de Rodrigues (62.95 à 63.95)', example: 63.4499)]
        public ?float $longitude = null,

        #[Groups(['admin_activity_point:write'])]
        #[ApiProperty(description: 'URL d\'un article lié (optionnelle). Doit commencer par "http://", "https://" ou "/".', example: '/blog/kitesurf-mourouk')]
        public ?string $articleUrl = null,
    ) {
    }
}
