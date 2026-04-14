<?php

declare(strict_types=1);

namespace App\Team\Domain\Port;

use App\Team\Domain\Entity\Team;
use Symfony\Component\Uid\Uuid;

interface TeamRepository
{
    public function findById(Uuid $id): ?Team;

    public function save(Team $team): void;
}
