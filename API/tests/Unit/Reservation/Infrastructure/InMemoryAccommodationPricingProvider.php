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

    /** @param array<array{name: string, price: float}> $billedExtraServices */
    public function set(Uuid $id, float $pricePerNight, ?float $weeklyPromotionPercentage = null, ?Uuid $teamId = null, ?int $maxGuests = null, bool $instantBooking = false, ?int $minNights = null, ?int $maxNights = null, array $billedExtraServices = []): void
    {
        $this->pricings[$id->toRfc4122()] = new AccommodationPricing($pricePerNight, $weeklyPromotionPercentage, $teamId, maxGuests: $maxGuests, instantBooking: $instantBooking, minNights: $minNights, maxNights: $maxNights, billedExtraServices: $billedExtraServices);
    }

    public function findByAccommodationId(Uuid $id): ?AccommodationPricing
    {
        return $this->pricings[$id->toRfc4122()] ?? null;
    }
}
