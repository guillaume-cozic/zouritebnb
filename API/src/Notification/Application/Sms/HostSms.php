<?php

declare(strict_types=1);

namespace App\Notification\Application\Sms;

/**
 * Builds the (short) SMS texts sent to a host. SMS bodies are plain text, kept concise.
 */
final readonly class HostSms
{
    public function reservationRequested(
        string $guestName,
        string $accommodationTitle,
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
    ): string {
        return \sprintf(
            'BnB Rodrigues : nouvelle demande de réservation de %s pour « %s » du %s au %s. Vous avez 24h pour répondre.',
            $guestName,
            $accommodationTitle,
            $checkIn->format('d/m/Y'),
            $checkOut->format('d/m/Y'),
        );
    }
}
