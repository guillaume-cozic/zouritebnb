<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
