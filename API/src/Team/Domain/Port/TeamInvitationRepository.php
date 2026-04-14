<?php

declare(strict_types=1);

namespace App\Team\Domain\Port;

use App\Team\Domain\Entity\TeamInvitation;
use Symfony\Component\Uid\Uuid;

interface TeamInvitationRepository
{
    public function save(TeamInvitation $invitation): void;

    public function findById(Uuid $id): ?TeamInvitation;

    /**
     * @return TeamInvitation[]
     */
    public function findPendingByTeam(Uuid $teamId): array;
}
