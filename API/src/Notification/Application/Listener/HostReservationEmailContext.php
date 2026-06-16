<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Shared\Domain\Port\UserContact;

/**
 * The data a host-facing reservation email needs, resolved across contexts.
 */
final readonly class HostReservationEmailContext
{
    /**
     * @param UserContact[] $hostContacts the team members to notify (host + co-hosts)
     */
    public function __construct(
        public array $hostContacts,
        public string $guestName,
        public string $accommodationTitle,
        public ?string $city,
        public \DateTimeImmutable $checkIn,
        public \DateTimeImmutable $checkOut,
    ) {
    }
}
