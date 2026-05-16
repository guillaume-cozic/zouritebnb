<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

interface UserTeamProvider
{
    public function teamIdOf(Uuid $userId): ?Uuid;
}
