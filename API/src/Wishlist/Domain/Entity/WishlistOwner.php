<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Entity;

use Symfony\Component\Uid\Uuid;

/**
 * Identifies who owns a wishlist: either an authenticated user (userId) or an
 * anonymous visitor tracked by a correlation id stored in a browser cookie.
 * Exactly one of the two identifiers is set.
 */
final readonly class WishlistOwner
{
    private function __construct(
        public ?Uuid $userId,
        public ?Uuid $correlationId,
    ) {
    }

    public static function user(Uuid $userId): self
    {
        return new self($userId, null);
    }

    public static function anonymous(Uuid $correlationId): self
    {
        return new self(null, $correlationId);
    }

    public function isUser(): bool
    {
        return null !== $this->userId;
    }
}
