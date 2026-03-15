<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Port\EventBus;

final class InMemoryEventBus implements EventBus
{
    /** @var DomainEvent[] */
    private array $events = [];

    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->events[] = $event;
        }
    }

    /** @return DomainEvent[] */
    public function getDispatchedEvents(): array
    {
        return $this->events;
    }
}
