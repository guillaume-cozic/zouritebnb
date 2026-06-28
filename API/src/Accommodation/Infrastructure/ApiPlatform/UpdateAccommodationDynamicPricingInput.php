<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateAccommodationDynamicPricingInput
{
    public function __construct(
        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Majoration des nuits du vendredi/samedi en %. Strictement > 0 et <= 500. Null pour désactiver.', example: 20.0)]
        #[Assert\GreaterThan(0)]
        #[Assert\LessThanOrEqual(500)]
        public ?float $weekendSurchargePercentage = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Remise last-minute en %. Strictement > 0 et <= 100. Requiert lastMinuteDays.', example: 15.0)]
        #[Assert\GreaterThan(0)]
        #[Assert\LessThanOrEqual(100)]
        public ?float $lastMinuteDiscountPercentage = null,

        #[Groups(['accommodation:write'])]
        #[ApiProperty(description: 'Fenêtre en jours avant l\'arrivée déclenchant la remise last-minute. >= 1. Requiert lastMinuteDiscountPercentage.', example: 7)]
        #[Assert\GreaterThanOrEqual(1)]
        public ?int $lastMinuteDays = null,
    ) {
    }
}
