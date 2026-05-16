<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Infrastructure;

use App\Shared\Domain\Port\TeamMembershipChecker;
use Symfony\Component\Uid\Uuid;

final class InMemoryTeamMembershipChecker implements TeamMembershipChecker
{
    /** @var array<string, string> mapping user RFC-4122 → team RFC-4122 */
    private array $memberships = [];

    public function add(Uuid $userId, Uuid $teamId): void
    {
        $this->memberships[$userId->toRfc4122()] = $teamId->toRfc4122();
    }

    public function isMember(Uuid $userId, Uuid $teamId): bool
    {
        return ($this->memberships[$userId->toRfc4122()] ?? null) === $teamId->toRfc4122();
    }
}
