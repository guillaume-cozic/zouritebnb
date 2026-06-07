<?php

declare(strict_types=1);

namespace App\Review\Domain\Port;

use Symfony\Component\Uid\Uuid;

/**
 * Read model describing a confirmed reservation whose stay has ended.
 *
 * Defined in the Review domain so it stays decoupled from the Reservation module;
 * the real implementation lives in infrastructure.
 */
final readonly class CompletedStay
{
    public function __construct(
        public Uuid $reservationId,
        public Uuid $accommodationId,
        public Uuid $guestUserId,
    ) {
    }
}
