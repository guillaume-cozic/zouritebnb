<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Entity;

use Symfony\Component\Uid\Uuid;

/**
 * A single accommodation saved to an owner's wishlist.
 */
final readonly class WishlistItem
{
    public function __construct(
        public Uuid $id,
        public WishlistOwner $owner,
        public Uuid $accommodationId,
    ) {
    }
}
