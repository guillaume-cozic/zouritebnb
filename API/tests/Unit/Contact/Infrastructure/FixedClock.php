<?php

declare(strict_types=1);

namespace App\Tests\Unit\Contact\Infrastructure;

use App\Shared\Domain\Port\Clock;

final class FixedClock implements Clock
{
    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }
}
