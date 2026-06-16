<?php

declare(strict_types=1);

namespace App\Notification\Application\Listener;

use App\Shared\Domain\Port\AccommodationSummaryProvider;
use App\Shared\Domain\Port\ReservationSummaryProvider;
use App\Shared\Domain\Port\TeamContactProvider;
use Symfony\Component\Uid\Uuid;

/**
 * Gathers, across contexts, everything a host-facing reservation email needs. Returns null
 * when nobody can be notified (no team contact, or missing/legacy data).
 */
final readonly class HostReservationEmailContextResolver
{
    public function __construct(
        private ReservationSummaryProvider $reservations,
        private AccommodationSummaryProvider $accommodations,
        private TeamContactProvider $teamContacts,
    ) {
    }

    public function resolve(Uuid $reservationId): ?HostReservationEmailContext
    {
        $reservation = $this->reservations->findById($reservationId);

        if (null === $reservation) {
            return null;
        }

        $hostContacts = $this->teamContacts->contactsOf($reservation->teamId);
        $accommodation = $this->accommodations->summaryOf($reservation->accommodationId);

        if ([] === $hostContacts || null === $accommodation) {
            return null;
        }

        return new HostReservationEmailContext(
            hostContacts: $hostContacts,
            guestName: $reservation->guestName,
            accommodationTitle: $accommodation->title,
            city: $accommodation->city,
            checkIn: $reservation->checkIn,
            checkOut: $reservation->checkOut,
        );
    }
}
