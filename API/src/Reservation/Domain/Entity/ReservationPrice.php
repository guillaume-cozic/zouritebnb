<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Exception\InvalidReservationException;

final readonly class ReservationPrice
{
    /**
     * Platform commission rate applied to the total price (the platform "margin").
     * Business setting — change here to update how new reservations are valued.
     */
    public const float COMMISSION_RATE = 0.08;

    /**
     * Share of the total price reversed to a solidarity project (the "contribution solidaire").
     * Business setting — change here to update how new reservations are valued.
     */
    public const float DONATION_RATE = 0.07;

    public function __construct(
        public float $totalPrice,
        public float $pricePerNight,
        public ?float $appliedDiscountPercentage,
        public float $commissionAmount = 0.0,
        public float $donationAmount = 0.0,
        public float $extraServicesTotal = 0.0,
    ) {
        if ($this->totalPrice < 0) {
            throw InvalidReservationException::becauseNegativeTotalPrice($this->totalPrice);
        }
        if ($this->pricePerNight < 0) {
            throw InvalidReservationException::becauseNegativePricePerNight($this->pricePerNight);
        }
    }

    /**
     * Builds the full financial snapshot of a stay: it computes and freezes the
     * platform commission (margin) and the solidarity donation at booking time so
     * that later rate changes never rewrite historical reservations. Both rates
     * apply to the full total, extra services billed with the reservation included.
     */
    public static function fromStay(float $totalPrice, float $pricePerNight, ?float $appliedDiscountPercentage, float $extraServicesTotal = 0.0): self
    {
        return new self(
            totalPrice: $totalPrice,
            pricePerNight: $pricePerNight,
            appliedDiscountPercentage: $appliedDiscountPercentage,
            commissionAmount: round($totalPrice * self::COMMISSION_RATE, 2),
            donationAmount: round($totalPrice * self::DONATION_RATE, 2),
            extraServicesTotal: $extraServicesTotal,
        );
    }
}
