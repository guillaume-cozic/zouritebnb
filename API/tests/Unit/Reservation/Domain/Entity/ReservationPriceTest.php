<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Exception\InvalidReservationException;
use PHPUnit\Framework\TestCase;

final class ReservationPriceTest extends TestCase
{
    public function test_should_create_a_valid_price(): void
    {
        $price = new ReservationPrice(
            totalPrice: 250.0,
            pricePerNight: 125.0,
            appliedDiscountPercentage: 10.0,
        );

        self::assertSame(250.0, $price->totalPrice);
        self::assertSame(125.0, $price->pricePerNight);
        self::assertSame(10.0, $price->appliedDiscountPercentage);
    }

    public function test_should_accept_null_discount(): void
    {
        $price = new ReservationPrice(
            totalPrice: 0.0,
            pricePerNight: 0.0,
            appliedDiscountPercentage: null,
        );

        self::assertSame(0.0, $price->totalPrice);
        self::assertSame(0.0, $price->pricePerNight);
        self::assertNull($price->appliedDiscountPercentage);
    }

    public function test_should_throw_when_total_price_is_negative(): void
    {
        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('Total price must be greater than or equal to zero, got -1.');

        new ReservationPrice(
            totalPrice: -1.0,
            pricePerNight: 10.0,
            appliedDiscountPercentage: null,
        );
    }

    public function test_should_throw_when_price_per_night_is_negative(): void
    {
        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('Price per night must be greater than or equal to zero, got -5.');

        new ReservationPrice(
            totalPrice: 10.0,
            pricePerNight: -5.0,
            appliedDiscountPercentage: null,
        );
    }

    public function test_should_default_commission_and_donation_to_zero(): void
    {
        $price = new ReservationPrice(
            totalPrice: 100.0,
            pricePerNight: 100.0,
            appliedDiscountPercentage: null,
        );

        self::assertSame(0.0, $price->commissionAmount);
        self::assertSame(0.0, $price->donationAmount);
    }

    public function test_from_stay_should_compute_commission_and_donation(): void
    {
        // 99 € × 4 nuits = 396 €, commission 8 % = 31,68 €, contribution solidaire 7 % = 27,72 €.
        $price = ReservationPrice::fromStay(
            totalPrice: 396.0,
            pricePerNight: 99.0,
            appliedDiscountPercentage: null,
        );

        self::assertSame(396.0, $price->totalPrice);
        self::assertSame(31.68, $price->commissionAmount);
        self::assertSame(27.72, $price->donationAmount);
        self::assertSame(0.0, $price->extraServicesTotal);
    }

    public function test_from_stay_should_snapshot_extra_services_and_rate_the_full_total(): void
    {
        // 400 € de nuits + 30 € de services facturés à la réservation = 430 €.
        // Commission et contribution s'appliquent au total complet, services inclus.
        $price = ReservationPrice::fromStay(
            totalPrice: 430.0,
            pricePerNight: 100.0,
            appliedDiscountPercentage: null,
            extraServicesTotal: 30.0,
        );

        self::assertSame(430.0, $price->totalPrice);
        self::assertSame(30.0, $price->extraServicesTotal);
        self::assertSame(34.4, $price->commissionAmount);
        self::assertSame(30.1, $price->donationAmount);
    }
}
