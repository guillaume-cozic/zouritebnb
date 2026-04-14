<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Infrastructure;

use App\Shared\Domain\Port\AccommodationPricing;
use App\Shared\Domain\Port\AccommodationPricingProvider;
use Symfony\Component\Uid\Uuid;

final class InMemoryAccommodationPricingProvider implements AccommodationPricingProvider
{
    /** @var array<string, AccommodationPricing> */
    private array $pricings = [];

    public function set(Uuid $id, float $pricePerNight, ?float $weeklyPromotionPercentage = null): void
    {
        $this->pricings[$id->toRfc4122()] = new AccommodationPricing($pricePerNight, $weeklyPromotionPercentage);
    }

    public function findByAccommodationId(Uuid $id): ?AccommodationPricing
    {
        return $this->pricings[$id->toRfc4122()] ?? null;
    }
}
