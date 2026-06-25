<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

/**
 * Cancellation policy snapshotted onto a reservation at booking time, so a later
 * change of the accommodation's policy never rewrites the terms of existing
 * reservations. The string values match the Accommodation module's policy codes,
 * which is the cross-module contract carried as a primitive.
 */
enum CancellationPolicy: string
{
    case Flexible = 'flexible';
    case Moderate = 'moderate';

    /** Defaults to the most traveller-friendly policy for reservations booked before the snapshot existed. */
    public static function fromString(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Flexible;
    }

    /**
     * Refundable share (in %) of the amount paid, given how long before check-in the
     * guest cancels.
     *
     * - Flexible: full refund up to 24h before check-in, nothing afterwards.
     * - Moderate: full refund up to 5 days before check-in, 50% afterwards.
     */
    public function refundPercentage(int $secondsUntilCheckIn): int
    {
        return match ($this) {
            self::Flexible => $secondsUntilCheckIn >= 24 * 3600 ? 100 : 0,
            self::Moderate => $secondsUntilCheckIn >= 5 * 24 * 3600 ? 100 : 50,
        };
    }
}
