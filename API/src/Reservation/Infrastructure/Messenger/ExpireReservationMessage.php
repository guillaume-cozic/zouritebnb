<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Messenger;

use Symfony\Component\Uid\Uuid;

/**
 * Messenger message used to schedule the auto-expiration check.
 * Carries the dispatch timestamp so the handler can verify the timeout
 * window has effectively elapsed (covers sync-transport replay in tests).
 */
final readonly class ExpireReservationMessage
{
    public function __construct(
        public Uuid $reservationId,
        public \DateTimeImmutable $dispatchedAt,
    ) {
    }
}
