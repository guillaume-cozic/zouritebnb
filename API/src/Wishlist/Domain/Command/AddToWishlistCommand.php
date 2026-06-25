<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Command;

use App\Wishlist\Domain\Entity\WishlistOwner;
use Symfony\Component\Uid\Uuid;

final readonly class AddToWishlistCommand
{
    public function __construct(
        public WishlistOwner $owner,
        public Uuid $accommodationId,
    ) {
    }
}
