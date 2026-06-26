<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationTypeInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Catégorie du logement. Valeurs autorisées : apartment, house, villa, studio, room, bungalow. Envoyer null pour ne pas spécifier.', example: 'villa')]
        #[Assert\Choice(choices: ['apartment', 'house', 'villa', 'studio', 'room', 'bungalow'])]
        public ?string $type = null,
    ) {
    }
}
