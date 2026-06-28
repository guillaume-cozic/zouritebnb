<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Command;

final readonly class RequestReservationModificationCommand
{
    public function __construct(
        public string $reservationId,
        public \DateTimeImmutable $checkIn,
        public \DateTimeImmutable $checkOut,
    ) {
    }
}
