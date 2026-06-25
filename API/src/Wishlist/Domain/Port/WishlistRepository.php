<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Port;

use App\Wishlist\Domain\Entity\WishlistItem;
use App\Wishlist\Domain\Entity\WishlistOwner;
use Symfony\Component\Uid\Uuid;

interface WishlistRepository
{
    /**
     * Persists a new wishlist item. Implementations must be idempotent: saving an
     * accommodation already on the owner's wishlist is a no-op.
     */
    public function add(WishlistItem $item): void;

    public function remove(WishlistOwner $owner, Uuid $accommodationId): void;

    public function exists(WishlistOwner $owner, Uuid $accommodationId): bool;

    /**
     * Re-attaches every item of an anonymous wishlist (correlation id) to a user,
     * dropping anonymous duplicates the user already saved. Used when an anonymous
     * visitor signs in.
     */
    public function transferOwnership(Uuid $correlationId, Uuid $userId): void;
}
