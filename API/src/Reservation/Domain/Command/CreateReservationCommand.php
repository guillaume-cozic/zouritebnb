<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class CreateReservationCommand
{
    public function __construct(
        public Uuid $accommodationId,
        public Uuid $teamId,
        public \DateTimeImmutable $checkIn,
        public \DateTimeImmutable $checkOut,
        public string $guestName,
        public int $guestCount = 1,
    ) {
    }
}
