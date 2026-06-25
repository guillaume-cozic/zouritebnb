<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidCancellationPolicyException extends \DomainException
{
    public static function becauseUnknown(?string $value): self
    {
        return new self(\sprintf('Unknown cancellation policy "%s". Allowed values: flexible, moderate.', $value ?? 'null'));
    }
}
