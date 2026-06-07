<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Security;

use App\Reservation\Domain\Entity\Reservation;
use App\Shared\Infrastructure\Security\CurrentUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Centralises the ownership rules of the Reservation module so that API processors
 * and providers do not duplicate authorization logic.
 *
 * - The "host" is any member of the team that owns the reservation (its accommodation).
 * - The "guest" is the user who requested the reservation (guestUserId).
 *
 * Each method throws a 403 AccessDeniedHttpException when the current user is not allowed.
 */
final readonly class ReservationAccessGuard
{
    public function assertHost(Reservation $reservation, CurrentUser $currentUser): void
    {
        if (!$this->isHost($reservation, $currentUser)) {
            throw new AccessDeniedHttpException('Only the host team can perform this action on the reservation.');
        }
    }

    public function assertHostOrGuest(Reservation $reservation, CurrentUser $currentUser): void
    {
        if (!$this->isHost($reservation, $currentUser) && !$this->isGuest($reservation, $currentUser)) {
            throw new AccessDeniedHttpException('You are not allowed to perform this action on the reservation.');
        }
    }

    public function isHost(Reservation $reservation, CurrentUser $currentUser): bool
    {
        return $reservation->getTeamId()->equals($currentUser->teamId());
    }

    public function isGuest(Reservation $reservation, CurrentUser $currentUser): bool
    {
        return $reservation->getGuestUserId()?->equals($currentUser->id()) ?? false;
    }
}
