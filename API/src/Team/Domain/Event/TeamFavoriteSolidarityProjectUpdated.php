<?php

declare(strict_types=1);

namespace App\Team\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class TeamFavoriteSolidarityProjectUpdated implements DomainEvent
{
    public function __construct(public Uuid $teamId)
    {
    }
}
