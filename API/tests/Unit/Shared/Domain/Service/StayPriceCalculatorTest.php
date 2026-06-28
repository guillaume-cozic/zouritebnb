<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Service;

use App\Shared\Domain\Port\AccommodationPricing;
use App\Shared\Domain\Service\StayPriceCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StayPriceCalculatorTest extends TestCase
{
    /** Well before any check-in used here, so last-minute pricing never kicks in unless tested. */
    private const string BOOKED_LONG_AGO = '2026-01-01T00:00:00+00:00';

    private StayPriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StayPriceCalculator();
    }

    public function test_should_charge_price_per_night_times_nights_without_promotion(): void
    {
        $price = $this->calculator->calculate(
            new AccommodationPricing(pricePerNight: 100.0, weeklyPromotionPercentage: null),
            new \DateTimeImmutable('2026-06-10T15:00:00+00:00'),
            new \DateTimeImmutable('2026-06-14T11:00:00+00:00'),
            new \DateTimeImmutable(self::BOOKED_LONG_AGO),
        );

        self::assertSame(400.0, $price->totalPrice);
        self::assertSame(100.0, $price->pricePerNight);
        self::assertNull($price->appliedDiscountPercentage);
        self::assertSame(40000, $price->amountInCents());
    }

    public function test_should_apply_weekly_promotion_from_seven_nights(): void
    {
        $price = $this->calculator->calculate(
            new AccommodationPricing(pricePerNight: 100.0, weeklyPromotionPercentage: 20.0),
            new \DateTimeImmutable('2026-06-10T15:00:00+00:00'),
            new \DateTimeImmutable('2026-06-17T11:00:00+00:00'),
            new \DateTimeImmutable(self::BOOKED_LONG_AGO),
        );

        self::assertSame(560.0, $price->totalPrice);
        self::assertSame(20.0, $price->appliedDiscountPercentage);
        self::assertSame(56000, $price->amountInCents());
    }

    public function test_should_not_apply_promotion_below_seven_nights(): void
    {
        $price = $this->calculator->calculate(
            new AccommodationPricing(pricePerNight: 100.0, weeklyPromotionPercentage: 20.0),
            new \DateTimeImmutable('2026-06-10T15:00:00+00:00'),
            new \DateTimeImmutable('2026-06-16T11:00:00+00:00'),
            new \DateTimeImmutable(self::BOOKED_LONG_AGO),
        );

        self::assertSame(600.0, $price->totalPrice);
        self::assertNull($price->appliedDiscountPercentage);
    }

    /**
     * @return \Generator<string, array{string, string, int}>
     */
    public static function nightsProvider(): \Generator
    {
        yield 'same day = zero nights' => ['2026-06-10T15:00:00+00:00', '2026-06-10T18:00:00+00:00', 0];
        yield 'one night' => ['2026-06-10T15:00:00+00:00', '2026-06-11T11:00:00+00:00', 10000];
    }

    #[DataProvider('nightsProvider')]
    public function test_should_count_nights_by_calendar_day(string $checkIn, string $checkOut, int $expectedCents): void
    {
        $price = $this->calculator->calculate(
            new AccommodationPricing(pricePerNight: 100.0, weeklyPromotionPercentage: null),
            new \DateTimeImmutable($checkIn),
            new \DateTimeImmutable($checkOut),
            new \DateTimeImmutable(self::BOOKED_LONG_AGO),
        );

        self::assertSame($expectedCents, $price->amountInCents());
    }

    public function test_should_surcharge_friday_and_saturday_nights(): void
    {
        // 2026-06-12 is a Friday; nights are Fri 12 + Sat 13 (check-out Sun 14 is not a night).
        $price = $this->calculator->calculate(
            new AccommodationPricing(pricePerNight: 100.0, weeklyPromotionPercentage: null, weekendSurchargePercentage: 50.0),
            new \DateTimeImmutable('2026-06-12T15:00:00+00:00'),
            new \DateTimeImmutable('2026-06-14T11:00:00+00:00'),
            new \DateTimeImmutable(self::BOOKED_LONG_AGO),
        );

        self::assertSame(300.0, $price->totalPrice);
        self::assertNull($price->appliedDiscountPercentage);
    }

    public function test_should_override_nightly_price_within_a_price_period(): void
    {
        // Both nights (Mon 15, Tue 16) fall inside the period → 200 each instead of the 100 base.
        $price = $this->calculator->calculate(
            new AccommodationPricing(
                pricePerNight: 100.0,
                weeklyPromotionPercentage: null,
                pricePeriods: [['startDate' => '2026-06-15', 'endDate' => '2026-06-16', 'pricePerNight' => 200.0]],
            ),
            new \DateTimeImmutable('2026-06-15T15:00:00+00:00'),
            new \DateTimeImmutable('2026-06-17T11:00:00+00:00'),
            new \DateTimeImmutable(self::BOOKED_LONG_AGO),
        );

        self::assertSame(400.0, $price->totalPrice);
    }

    public function test_should_apply_last_minute_discount_within_the_window(): void
    {
        // Booked 2 days before check-in, window is 7 days → 10% off two 100€ nights.
        $price = $this->calculator->calculate(
            new AccommodationPricing(pricePerNight: 100.0, weeklyPromotionPercentage: null, lastMinuteDiscountPercentage: 10.0, lastMinuteDays: 7),
            new \DateTimeImmutable('2026-06-15T15:00:00+00:00'),
            new \DateTimeImmutable('2026-06-17T11:00:00+00:00'),
            new \DateTimeImmutable('2026-06-13T09:00:00+00:00'),
        );

        self::assertSame(180.0, $price->totalPrice);
        self::assertSame(10.0, $price->appliedDiscountPercentage);
    }

    public function test_should_not_apply_last_minute_discount_outside_the_window(): void
    {
        // Booked 14 days before check-in, window is 7 days → no discount.
        $price = $this->calculator->calculate(
            new AccommodationPricing(pricePerNight: 100.0, weeklyPromotionPercentage: null, lastMinuteDiscountPercentage: 10.0, lastMinuteDays: 7),
            new \DateTimeImmutable('2026-06-15T15:00:00+00:00'),
            new \DateTimeImmutable('2026-06-17T11:00:00+00:00'),
            new \DateTimeImmutable('2026-06-01T09:00:00+00:00'),
        );

        self::assertSame(200.0, $price->totalPrice);
        self::assertNull($price->appliedDiscountPercentage);
    }

    public function test_should_apply_the_best_of_weekly_and_last_minute_discounts(): void
    {
        // 7 nights → weekly 10% eligible; booked last-minute → 30% eligible; the larger wins.
        $price = $this->calculator->calculate(
            new AccommodationPricing(pricePerNight: 100.0, weeklyPromotionPercentage: 10.0, lastMinuteDiscountPercentage: 30.0, lastMinuteDays: 7),
            new \DateTimeImmutable('2026-06-15T15:00:00+00:00'),
            new \DateTimeImmutable('2026-06-22T11:00:00+00:00'),
            new \DateTimeImmutable('2026-06-10T09:00:00+00:00'),
        );

        self::assertSame(30.0, $price->appliedDiscountPercentage);
        // 7 nights × 100 = 700, −30% = 490.
        self::assertSame(490.0, $price->totalPrice);
    }
}
