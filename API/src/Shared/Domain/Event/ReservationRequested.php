<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use Symfony\Component\Uid\Uuid;

/**
 * Integration event published by the Reservation context and consumed by other contexts
 * (e.g. Conversation, Notification). It lives in Shared because it is the published
 * language of the "a guest requested a reservation" fact across modules.
 */
final readonly class ReservationRequested implements DomainEvent
{
    public function __construct(
        public Uuid $reservationId,
        public Uuid $guestUserId,
        public ?string $note = null,
        public ?string $paymentIntentId = null,
    ) {
    }
}
