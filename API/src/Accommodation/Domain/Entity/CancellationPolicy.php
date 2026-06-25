<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidCancellationPolicyException;

/**
 * Cancellation policy chosen by the host for an accommodation.
 *
 * - Flexible: full refund up to 24h before check-in.
 * - Moderate: full refund up to 5 days before check-in, then 50%.
 *
 * Only the host-selectable policies are modeled here.
 */
enum CancellationPolicy: string
{
    case Flexible = 'flexible';
    case Moderate = 'moderate';

    public static function fromString(?string $value): self
    {
        return self::tryFrom((string) $value)
            ?? throw InvalidCancellationPolicyException::becauseUnknown($value);
    }
}
