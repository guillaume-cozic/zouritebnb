<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published by the User context when a new account is created.
 * Consumers in other contexts can react — e.g. Notification queues a welcome email.
 */
final readonly class UserRegistered implements DomainEvent
{
    public function __construct(public Uuid $userId, public Uuid $teamId)
    {
    }
}
