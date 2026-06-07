<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Infrastructure;

use App\Team\Domain\Entity\Team;
use App\Team\Domain\Port\TeamRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryTeamRepository implements TeamRepository
{
    /** @var Team[] */
    private array $teams = [];

    public function findById(Uuid $id): ?Team
    {
        return $this->teams[$id->toRfc4122()] ?? null;
    }

    public function save(Team $team): void
    {
        $this->teams[$team->getId()->toRfc4122()] = $team;
    }
}
