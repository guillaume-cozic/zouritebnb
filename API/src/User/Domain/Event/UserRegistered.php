<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class UserRegistered implements DomainEvent
{
    public function __construct(public Uuid $userId, public Uuid $teamId)
    {
    }
}
