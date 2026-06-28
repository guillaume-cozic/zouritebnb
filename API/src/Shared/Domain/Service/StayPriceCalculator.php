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
 *
 * Pricing is computed night by night:
 *  1. the nightly base is the matching price-period override, else the flat price;
 *  2. a weekend surcharge is applied on Friday and Saturday nights;
 *  3. the best single stay-level discount (weekly promotion vs last-minute, never
 *     stacked) is applied to the resulting subtotal.
 */
final readonly class StayPriceCalculator
{
    private const int WEEKLY_PROMOTION_MIN_NIGHTS = 7;

    public function calculate(
        AccommodationPricing $pricing,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
        \DateTimeImmutable $bookedAt,
    ): StayPrice {
        $checkInDay = $checkIn->setTime(0, 0);
        $checkOutDay = $checkOut->setTime(0, 0);
        $nights = (int) $checkInDay->diff($checkOutDay)->days;

        $subtotal = 0.0;
        for ($night = $checkInDay; $night < $checkOutDay; $night = $night->modify('+1 day')) {
            $nightly = $this->nightlyBase($pricing, $night);
            if (self::isWeekendNight($night) && null !== $pricing->weekendSurchargePercentage) {
                $nightly *= 1 + $pricing->weekendSurchargePercentage / 100;
            }
            $subtotal += $nightly;
        }

        $discount = $this->stayDiscount($pricing, $nights, $checkInDay, $bookedAt);
        $total = $subtotal * (1 - ($discount ?? 0.0) / 100);

        return new StayPrice(
            totalPrice: round($total, 2),
            pricePerNight: $pricing->pricePerNight,
            appliedDiscountPercentage: $discount,
        );
    }

    /** Matching price-period override for that night, else the flat nightly price. */
    private function nightlyBase(AccommodationPricing $pricing, \DateTimeImmutable $night): float
    {
        $date = $night->format('Y-m-d');

        foreach ($pricing->pricePeriods as $period) {
            if (($period['startDate'] ?? '') <= $date && $date <= ($period['endDate'] ?? '')) {
                return (float) $period['pricePerNight'];
            }
        }

        return $pricing->pricePerNight;
    }

    /** Friday or Saturday night (the nights charged at the weekend rate). */
    private static function isWeekendNight(\DateTimeImmutable $night): bool
    {
        $dayOfWeek = (int) $night->format('N');

        return 5 === $dayOfWeek || 6 === $dayOfWeek;
    }

    /** Best of the weekly-stay promotion and the last-minute discount — they never stack. */
    private function stayDiscount(
        AccommodationPricing $pricing,
        int $nights,
        \DateTimeImmutable $checkInDay,
        \DateTimeImmutable $bookedAt,
    ): ?float {
        $candidates = [];

        if ($nights >= self::WEEKLY_PROMOTION_MIN_NIGHTS && null !== $pricing->weeklyPromotionPercentage) {
            $candidates[] = $pricing->weeklyPromotionPercentage;
        }

        if (null !== $pricing->lastMinuteDiscountPercentage && null !== $pricing->lastMinuteDays) {
            $bookedDay = $bookedAt->setTime(0, 0);
            if ($bookedDay <= $checkInDay) {
                $daysUntilCheckIn = (int) $bookedDay->diff($checkInDay)->days;
                if ($daysUntilCheckIn < $pricing->lastMinuteDays) {
                    $candidates[] = $pricing->lastMinuteDiscountPercentage;
                }
            }
        }

        return [] === $candidates ? null : max($candidates);
    }
}
