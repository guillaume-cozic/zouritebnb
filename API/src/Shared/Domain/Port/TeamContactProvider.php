<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

interface TeamContactProvider
{
    /**
     * The contacts (host + co-hosts) belonging to a team, e.g. to notify them of a booking.
     *
     * @return UserContact[]
     */
    public function contactsOf(Uuid $teamId): array;
}
