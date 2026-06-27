<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Command;

final readonly class CancelReservationCommand
{
    public function __construct(
        public string $reservationId,
        public ?string $message = null,
        public bool $byHost = false,
    ) {
    }
}
