<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

/**
 * Result of pricing a stay: the total to charge plus the breakdown used by the
 * reservation (nightly rate, any applied weekly-promotion discount, and the
 * extra services billed with the reservation — already included in the total).
 */
final readonly class StayPrice
{
    public function __construct(
        public float $totalPrice,
        public float $pricePerNight,
        public ?float $appliedDiscountPercentage,
        public float $extraServicesTotal = 0.0,
    ) {
    }

    /** Total amount expressed in the currency's smallest unit (cents), for payment gateways. */
    public function amountInCents(): int
    {
        return (int) round($this->totalPrice * 100);
    }
}
