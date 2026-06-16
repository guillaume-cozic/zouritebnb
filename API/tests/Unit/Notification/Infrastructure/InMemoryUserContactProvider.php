<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure;

use App\Shared\Domain\Port\UserContact;
use App\Shared\Domain\Port\UserContactProvider;
use Symfony\Component\Uid\Uuid;

final class InMemoryUserContactProvider implements UserContactProvider
{
    /** @var array<string, UserContact> */
    private array $contacts = [];

    public function add(UserContact $contact): void
    {
        $this->contacts[$contact->userId->toRfc4122()] = $contact;
    }

    public function contactOf(Uuid $userId): ?UserContact
    {
        return $this->contacts[$userId->toRfc4122()] ?? null;
    }
}
