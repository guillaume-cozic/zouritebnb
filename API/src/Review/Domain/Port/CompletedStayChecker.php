<?php

declare(strict_types=1);

namespace App\Review\Domain\Port;

use Symfony\Component\Uid\Uuid;

/**
 * Port answering whether a given guest had a confirmed stay at a given accommodation
 * whose checkout date is in the past. Keeps the Review domain decoupled from the
 * Reservation module; the real implementation lives in infrastructure.
 */
interface CompletedStayChecker
{
    public function hasCompletedStay(Uuid $guestUserId, Uuid $accommodationId): bool;

    /**
     * Returns the completed stay for the given guest and accommodation, or null when none exists.
     */
    public function findCompletedStay(Uuid $guestUserId, Uuid $accommodationId): ?CompletedStay;
}
