<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published by the Team context when a co-host is invited. Consumers in
 * other contexts can react — e.g. Notification queues the invitation email.
 */
final readonly class CoHostInvited implements DomainEvent
{
    public function __construct(
        public Uuid $invitationId,
        public Uuid $teamId,
        public string $email,
    ) {
    }
}
