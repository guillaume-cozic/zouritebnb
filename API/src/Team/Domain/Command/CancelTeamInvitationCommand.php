<?php

declare(strict_types=1);

namespace App\Team\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class CancelTeamInvitationCommand
{
    public function __construct(
        public Uuid $invitationId,
    ) {
    }
}
