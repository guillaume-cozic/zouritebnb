<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Infrastructure;

use App\Team\Domain\Entity\InvitationStatus;
use App\Team\Domain\Entity\TeamInvitation;
use App\Team\Domain\Port\TeamInvitationRepository;
use Symfony\Component\Uid\Uuid;

final class InMemoryTeamInvitationRepository implements TeamInvitationRepository
{
    /** @var TeamInvitation[] */
    private array $invitations = [];

    public function save(TeamInvitation $invitation): void
    {
        $this->invitations[$invitation->getId()->toRfc4122()] = $invitation;
    }

    public function findById(Uuid $id): ?TeamInvitation
    {
        return $this->invitations[$id->toRfc4122()] ?? null;
    }

    public function findPendingByTeam(Uuid $teamId): array
    {
        $result = [];
        foreach ($this->invitations as $invitation) {
            if (!$invitation->getTeamId()->equals($teamId)) {
                continue;
            }
            if (InvitationStatus::Pending !== $invitation->getStatus()) {
                continue;
            }
            $result[] = $invitation;
        }

        return $result;
    }
}
