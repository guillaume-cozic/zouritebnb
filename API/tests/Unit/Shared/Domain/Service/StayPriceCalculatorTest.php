<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Service;

use App\Shared\Domain\Port\AccommodationPricing;
use App\Shared\Domain\Service\StayPriceCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StayPriceCalculatorTest extends TestCase
{
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
        );

        self::assertSame($expectedCents, $price->amountInCents());
    }
}
