<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationWeeklyPromotionInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Pourcentage de réduction appliqué aux séjours d\'au moins 7 nuits. Doit être strictement supérieur à 0 et inférieur ou égal à 100. Null pour désactiver.', example: 10.0)]
        #[Assert\GreaterThan(0)]
        #[Assert\LessThanOrEqual(100)]
        public ?float $weeklyPromotionPercentage = null,
    ) {
    }
}
