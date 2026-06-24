<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

/**
 * Immutable financial document for a paid reservation: the issuing platform, the
 * parties (host and guest), the stay, and the line items that add up to the total
 * actually paid by the guest. Built from a confirmed reservation's frozen price.
 */
final readonly class Invoice
{
    /** @param InvoiceLine[] $lines */
    public function __construct(
        public string $number,
        public \DateTimeImmutable $issuedAt,
        public string $sellerName,
        public ?string $hostName,
        public string $guestName,
        public ?string $accommodationTitle,
        public ?string $accommodationCity,
        public \DateTimeImmutable $checkIn,
        public \DateTimeImmutable $checkOut,
        public int $nights,
        public array $lines,
        public float $total,
        public string $currency,
    ) {
    }
}
