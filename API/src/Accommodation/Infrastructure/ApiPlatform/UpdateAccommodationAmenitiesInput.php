<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationAmenitiesInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Liste des codes d\'équipements', example: ['private_pool', 'wifi', 'parking'])]
        #[Assert\All([
            new Assert\Type('string'),
            new Assert\NotBlank(normalizer: 'trim'),
        ])]
        public ?array $codes = null,
    ) {
    }
}
