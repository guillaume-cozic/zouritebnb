<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

/**
 * What the guest gets back if they cancel now: the applicable policy, the amount
 * they paid, and the refundable share computed from how close to check-in they are.
 */
final readonly class RefundBreakdown
{
    public function __construct(
        public CancellationPolicy $policy,
        public float $totalPaid,
        public float $refundAmount,
        public int $refundPercentage,
    ) {
    }
}
