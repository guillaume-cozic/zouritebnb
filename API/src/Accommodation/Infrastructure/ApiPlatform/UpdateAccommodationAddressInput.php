<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationAddressInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Rue', example: '12 rue de la Paix')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $street = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Ville', example: 'Paris')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public ?string $city = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Code postal', example: '75002')]
        #[Assert\Length(max: 20)]
        public ?string $zipCode = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Pays', example: 'France')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public ?string $country = null,
    ) {
    }
}
