<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationExtraServicesInput
{
    /**
     * @param array<array{name: string, price: float, billedWithReservation?: bool}> $extraServices
     */
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Liste complète des services supplémentaires proposés par l\'hôte. Chaque entrée : name (non vide, max 100 caractères), price (strictement positif, en euros) et billedWithReservation (booléen, false par défaut). Les services avec billedWithReservation à true sont ajoutés au montant payé à la réservation ; les autres sont réglés sur place. Remplace l\'intégralité des services existants.', example: [['name' => 'Ménage', 'price' => 30.0, 'billedWithReservation' => true], ['name' => 'Petit-déjeuner', 'price' => 12.5, 'billedWithReservation' => false]])]
        public array $extraServices = [],
    ) {
    }
}
