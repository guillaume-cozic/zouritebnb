<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AccommodationInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nom de l\'hébergement', example: 'Chalet Montagne')]
        #[Assert\Length(max: 255)]
        public string $title = '',

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Description détaillée de l\'hébergement', example: 'Un chalet chaleureux au pied des pistes...')]
        public string $description = '',

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Prix par nuit en euros, doit être strictement positif', example: 150.0)]
        #[Assert\NotNull]
        #[Assert\Positive]
        public ?float $price = null,
    ) {
    }
}
