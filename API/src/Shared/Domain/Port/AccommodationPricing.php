<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

final readonly class AccommodationPricing
{
    public function __construct(
        public float $pricePerNight,
        public ?float $weeklyPromotionPercentage,
    ) {
    }
}
