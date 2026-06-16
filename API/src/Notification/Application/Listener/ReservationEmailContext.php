<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Shared\Domain\Port\UserContact;
use Symfony\Component\Uid\Uuid;

/**
 * The data a reservation email needs, resolved from the reservation, its guest and its
 * accommodation across contexts.
 */
final readonly class ReservationEmailContext
{
    public function __construct(
        public UserContact $guest,
        public string $accommodationTitle,
        public ?string $city,
        public \DateTimeImmutable $checkIn,
        public \DateTimeImmutable $checkOut,
    ) {
    }

    public function guestId(): Uuid
    {
        return $this->guest->userId;
    }
}
