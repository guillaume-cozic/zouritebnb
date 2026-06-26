<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationStayConstraintsInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre minimum de nuits par séjour. Envoyer null pour aucune contrainte.', example: 2)]
        #[Assert\Positive]
        public ?int $minNights = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Nombre maximum de nuits par séjour. Envoyer null pour aucune contrainte.', example: 30)]
        #[Assert\Positive]
        #[Assert\GreaterThanOrEqual(propertyPath: 'minNights', message: 'maxNights doit être supérieur ou égal à minNights.')]
        public ?int $maxNights = null,
    ) {
    }
}
