<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\User\Infrastructure\Doctrine\UserEntity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * Reusable helper for API processors that need the currently authenticated user.
 *
 * Resolves the JWT-authenticated user from the Symfony security context and exposes
 * its domain identity (UUID + team UUID). Throws a 401 when there is no authenticated
 * user, so processors never need to deal with anonymous access explicitly.
 *
 * Usage in a processor:
 *
 *     public function __construct(private CurrentUser $currentUser) {}
 *
 *     public function process(...): void
 *     {
 *         $userId = $this->currentUser->id();      // Symfony\Component\Uid\Uuid
 *         $teamId = $this->currentUser->teamId();  // Symfony\Component\Uid\Uuid
 *     }
 */
final readonly class CurrentUser
{
    public function __construct(private Security $security)
    {
    }

    /**
     * Domain identifier (UUID) of the authenticated user.
     *
     * @throws UnauthorizedHttpException when no user is authenticated
     */
    public function id(): Uuid
    {
        return $this->userEntity()->getId();
    }

    /**
     * Domain identifier (UUID) of the authenticated user, or null when the request
     * is anonymous. Use this in operations that are open to anonymous visitors.
     */
    public function idOrNull(): ?Uuid
    {
        $user = $this->security->getUser();

        return $user instanceof UserEntity ? $user->getId() : null;
    }

    /**
     * Team identifier (UUID) of the authenticated user.
     *
     * @throws UnauthorizedHttpException when no user is authenticated
     */
    public function teamId(): Uuid
    {
        return $this->userEntity()->getTeamId();
    }

    /**
     * Email / security identifier of the authenticated user.
     *
     * @throws UnauthorizedHttpException when no user is authenticated
     */
    public function email(): string
    {
        return (string) $this->userEntity()->getEmail();
    }

    private function userEntity(): UserEntity
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserEntity) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        return $user;
    }
}
