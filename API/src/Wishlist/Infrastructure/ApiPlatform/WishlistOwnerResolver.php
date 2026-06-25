<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\ApiPlatform;

use App\Shared\Infrastructure\Security\CurrentUser;
use App\Wishlist\Domain\Entity\WishlistOwner;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

/**
 * Resolves the owner of a wishlist request: the authenticated user when a JWT is
 * present, otherwise an anonymous owner identified by the correlation id carried
 * in the X-Wishlist-Id header (persisted client-side as a cookie).
 */
final readonly class WishlistOwnerResolver
{
    public const string CORRELATION_HEADER = 'X-Wishlist-Id';

    public function __construct(
        private CurrentUser $currentUser,
        private RequestStack $requestStack,
    ) {
    }

    public function resolve(): ?WishlistOwner
    {
        $userId = $this->currentUser->idOrNull();
        if (null !== $userId) {
            return WishlistOwner::user($userId);
        }

        $correlationId = $this->correlationId();

        return null === $correlationId ? null : WishlistOwner::anonymous($correlationId);
    }

    public function correlationId(): ?Uuid
    {
        $raw = $this->requestStack->getCurrentRequest()?->headers->get(self::CORRELATION_HEADER);

        return \is_string($raw) && Uuid::isValid($raw) ? Uuid::fromString($raw) : null;
    }
}
