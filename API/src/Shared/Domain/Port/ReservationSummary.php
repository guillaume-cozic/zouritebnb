<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

final readonly class ReservationSummary
{
    public function __construct(
        public Uuid $reservationId,
        public Uuid $accommodationId,
        public Uuid $teamId,
        public ?Uuid $guestUserId,
        public string $guestName,
        public \DateTimeImmutable $checkIn,
        public \DateTimeImmutable $checkOut,
    ) {
    }
}
