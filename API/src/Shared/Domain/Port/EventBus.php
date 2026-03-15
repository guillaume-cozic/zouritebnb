<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use App\Shared\Domain\Event\DomainEvent;

interface EventBus
{
    /** @param DomainEvent[] $events */
    public function dispatch(array $events): void;
}
