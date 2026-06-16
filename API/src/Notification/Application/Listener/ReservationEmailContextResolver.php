<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Shared\Domain\Port\AccommodationSummaryProvider;
use App\Shared\Domain\Port\ReservationSummaryProvider;
use App\Shared\Domain\Port\UserContactProvider;
use Symfony\Component\Uid\Uuid;

/**
 * Gathers, across contexts, everything a reservation email needs. Returns null when the
 * reservation cannot be emailed (e.g. a back-office reservation with no guest account,
 * or missing/legacy data) so listeners can simply skip.
 */
final readonly class ReservationEmailContextResolver
{
    public function __construct(
        private ReservationSummaryProvider $reservations,
        private AccommodationSummaryProvider $accommodations,
        private UserContactProvider $contacts,
    ) {
    }

    public function resolve(Uuid $reservationId): ?ReservationEmailContext
    {
        $reservation = $this->reservations->findById($reservationId);

        if (null === $reservation || null === $reservation->guestUserId) {
            return null;
        }

        $guest = $this->contacts->contactOf($reservation->guestUserId);
        $accommodation = $this->accommodations->summaryOf($reservation->accommodationId);

        if (null === $guest || null === $accommodation) {
            return null;
        }

        return new ReservationEmailContext(
            guest: $guest,
            accommodationTitle: $accommodation->title,
            city: $accommodation->city,
            checkIn: $reservation->checkIn,
            checkOut: $reservation->checkOut,
        );
    }
}
