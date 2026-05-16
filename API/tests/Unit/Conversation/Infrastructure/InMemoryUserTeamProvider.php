<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Infrastructure;

use App\Shared\Domain\Port\UserTeamProvider;
use Symfony\Component\Uid\Uuid;

final class InMemoryUserTeamProvider implements UserTeamProvider
{
    /** @var array<string, Uuid> */
    private array $teamByUser = [];

    public function set(Uuid $userId, Uuid $teamId): void
    {
        $this->teamByUser[$userId->toRfc4122()] = $teamId;
    }

    public function teamIdOf(Uuid $userId): ?Uuid
    {
        return $this->teamByUser[$userId->toRfc4122()] ?? null;
    }
}
