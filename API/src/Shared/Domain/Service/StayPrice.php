<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

/**
 * Result of pricing a stay: the total to charge plus the breakdown used by the
 * reservation (nightly rate and any applied weekly-promotion discount).
 */
final readonly class StayPrice
{
    public function __construct(
        public float $totalPrice,
        public float $pricePerNight,
        public ?float $appliedDiscountPercentage,
    ) {
    }

    /** Total amount expressed in the currency's smallest unit (cents), for payment gateways. */
    public function amountInCents(): int
    {
        return (int) round($this->totalPrice * 100);
    }
}
