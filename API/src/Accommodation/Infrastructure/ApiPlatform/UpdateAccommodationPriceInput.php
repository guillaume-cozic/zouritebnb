<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class UpdateAccommodationPriceInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nouveau prix par nuit en euros, doit être strictement positif', example: 200.0)]
        public ?float $price = null,
    ) {
    }
}
