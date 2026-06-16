<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

final class EmailDeliveryException extends \RuntimeException
{
    public static function because(string $reason, ?\Throwable $previous = null): self
    {
        return new self(\sprintf('Email delivery failed: %s', $reason), 0, $previous);
    }
}
