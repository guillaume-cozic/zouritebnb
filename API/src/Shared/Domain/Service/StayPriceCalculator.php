<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

use App\Shared\Domain\Port\AccommodationPricing;

/**
 * Single source of truth for pricing a stay from an accommodation's pricing and
 * a date range. Shared by the reservation use cases and the payment-intent use
 * case so the amount a guest is charged can never diverge from the amount their
 * reservation records — which is exactly what lets the payment amount be derived
 * server-side instead of trusted from the client.
 */
final readonly class StayPriceCalculator
{
    private const int WEEKLY_PROMOTION_MIN_NIGHTS = 7;

    public function calculate(
        AccommodationPricing $pricing,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): StayPrice {
        $nights = (int) $checkIn->setTime(0, 0)->diff($checkOut->setTime(0, 0))->days;

        if ($nights >= self::WEEKLY_PROMOTION_MIN_NIGHTS && null !== $pricing->weeklyPromotionPercentage) {
            $discountedPricePerNight = $pricing->pricePerNight * (1 - $pricing->weeklyPromotionPercentage / 100);
            $appliedDiscount = $pricing->weeklyPromotionPercentage;
        } else {
            $discountedPricePerNight = $pricing->pricePerNight;
            $appliedDiscount = null;
        }

        return new StayPrice(
            totalPrice: $discountedPricePerNight * $nights,
            pricePerNight: $pricing->pricePerNight,
            appliedDiscountPercentage: $appliedDiscount,
        );
    }
}
