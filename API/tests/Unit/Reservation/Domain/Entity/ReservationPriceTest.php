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
}
