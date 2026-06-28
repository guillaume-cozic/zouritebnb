<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationPricePeriodsInput
{
    /**
     * @param array<array{startDate: string, endDate: string, pricePerNight: float}> $pricePeriods
     */
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Liste complète des tarifs par période. Chaque entrée : startDate/endDate (Y-m-d, endDate >= startDate) et pricePerNight (> 0). Remplace l\'intégralité des périodes existantes.', example: [['startDate' => '2026-07-01', 'endDate' => '2026-08-31', 'pricePerNight' => 250.0]])]
        public array $pricePeriods = [],
    ) {
    }
}
