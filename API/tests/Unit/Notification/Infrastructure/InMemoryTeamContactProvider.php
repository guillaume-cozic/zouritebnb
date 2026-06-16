<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Shared\Domain\Port\TeamContactProvider;
use App\Shared\Domain\Port\UserContact;
use Symfony\Component\Uid\Uuid;

final class InMemoryTeamContactProvider implements TeamContactProvider
{
    /** @var array<string, UserContact[]> */
    private array $contactsByTeam = [];

    public function addContact(Uuid $teamId, UserContact $contact): void
    {
        $this->contactsByTeam[$teamId->toRfc4122()][] = $contact;
    }

    public function contactsOf(Uuid $teamId): array
    {
        return $this->contactsByTeam[$teamId->toRfc4122()] ?? [];
    }
}
