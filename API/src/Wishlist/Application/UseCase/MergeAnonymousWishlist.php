<?php

declare(strict_types=1);

namespace App\Wishlist\Application\UseCase;

use App\Wishlist\Domain\Port\WishlistRepository;
use Symfony\Component\Uid\Uuid;

/**
 * Merges an anonymous wishlist (tracked by a cookie correlation id) into a user's
 * account, typically right after sign-in.
 */
final readonly class MergeAnonymousWishlist
{
    public function __construct(
        private WishlistRepository $repository,
    ) {
    }

    public function handle(Uuid $correlationId, Uuid $userId): void
    {
        $this->repository->transferOwnership($correlationId, $userId);
    }
}
