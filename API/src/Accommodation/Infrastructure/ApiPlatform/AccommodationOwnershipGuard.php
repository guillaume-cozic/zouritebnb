<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Infrastructure\Security\CurrentUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * Guards write operations on an accommodation: ensures the authenticated user
 * belongs to the team owning the accommodation.
 *
 * Throws:
 * - 404 AccommodationNotFoundException when the accommodation does not exist
 * - 403 AccessDeniedHttpException when the current user's team is not the owner
 * - 401 UnauthorizedHttpException (via CurrentUser) when no user is authenticated
 */
final readonly class AccommodationOwnershipGuard
{
    public function __construct(
        private AccommodationRepository $repository,
        private CurrentUser $currentUser,
    ) {
    }

    public function assertOwnedByCurrentUser(Uuid $accommodationId): void
    {
        $accommodation = $this->repository->findById($accommodationId);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($accommodationId->toRfc4122());
        }

        $ownerTeamId = $accommodation->getTeamId();

        if (null === $ownerTeamId || !$ownerTeamId->equals($this->currentUser->teamId())) {
            throw new AccessDeniedHttpException('You are not allowed to modify this accommodation.');
        }
    }
}
