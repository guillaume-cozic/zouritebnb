<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationExtraServicesInput
{
    /**
     * @param array<array{name: string, price: float}> $extraServices
     */
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Liste complète des services supplémentaires proposés par l\'hôte. Chaque entrée : name (non vide, max 100 caractères) et price (strictement positif, en euros). Remplace l\'intégralité des services existants.', example: [['name' => 'Ménage', 'price' => 30.0], ['name' => 'Petit-déjeuner', 'price' => 12.5]])]
        public array $extraServices = [],
    ) {
    }
}
