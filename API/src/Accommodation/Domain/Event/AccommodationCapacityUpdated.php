<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class AccommodationCapacityUpdated implements DomainEvent
{
    public function __construct(public Uuid $accommodationId)
    {
    }
}
