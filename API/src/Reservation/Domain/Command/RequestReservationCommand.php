<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class RequestReservationCommand
{
    public function __construct(
        public Uuid $accommodationId,
        public Uuid $guestUserId,
        public \DateTimeImmutable $checkIn,
        public \DateTimeImmutable $checkOut,
        public string $guestName,
        public ?string $note = null,
        public ?string $paymentIntentId = null,
    ) {
    }
}
