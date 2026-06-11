<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationCapacityInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre de chambres', example: 3)]
        #[Assert\NotNull]
        #[Assert\PositiveOrZero]
        public ?int $bedrooms = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre de salles de bain', example: 2)]
        #[Assert\NotNull]
        #[Assert\PositiveOrZero]
        public ?int $bathrooms = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre maximum de voyageurs', example: 6)]
        #[Assert\NotNull]
        #[Assert\PositiveOrZero]
        public ?int $maxGuests = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre de lits simples', example: 2)]
        #[Assert\NotNull]
        #[Assert\PositiveOrZero]
        public ?int $singleBeds = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre de lits doubles', example: 2)]
        #[Assert\NotNull]
        #[Assert\PositiveOrZero]
        public ?int $doubleBeds = null,
    ) {
    }
}
