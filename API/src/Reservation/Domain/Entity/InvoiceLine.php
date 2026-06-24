<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

/** A single billed line of an invoice: a human label and its amount. */
final readonly class InvoiceLine
{
    public function __construct(
        public string $label,
        public float $amount,
    ) {
    }
}
