<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

use App\Reservation\Domain\Exception\InvalidReservationException;

final readonly class ReservationPrice
{
    public function __construct(
        public float $totalPrice,
        public float $pricePerNight,
        public ?float $appliedDiscountPercentage,
    ) {
        if ($this->totalPrice < 0) {
            throw InvalidReservationException::becauseNegativeTotalPrice($this->totalPrice);
        }
        if ($this->pricePerNight < 0) {
            throw InvalidReservationException::becauseNegativePricePerNight($this->pricePerNight);
        }
    }
}
