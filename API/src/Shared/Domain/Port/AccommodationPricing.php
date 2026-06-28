<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

final readonly class AccommodationPricing
{
    /**
     * @param array<array{startDate: string, endDate: string, pricePerNight: float}> $pricePeriods
     */
    public function __construct(
        public float $pricePerNight,
        public ?float $weeklyPromotionPercentage,
        public ?Uuid $teamId = null,
        public ?string $cancellationPolicy = null,
        public ?int $maxGuests = null,
        public bool $instantBooking = false,
        public ?int $minNights = null,
        public ?int $maxNights = null,
        public ?float $weekendSurchargePercentage = null,
        public ?float $lastMinuteDiscountPercentage = null,
        public ?int $lastMinuteDays = null,
        public array $pricePeriods = [],
    ) {
    }
}
