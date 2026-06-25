<?php

declare(strict_types=1);

namespace App\Wishlist\Application\UseCase;

use App\Wishlist\Domain\Command\RemoveFromWishlistCommand;
use App\Wishlist\Domain\Port\WishlistRepository;

final readonly class RemoveFromWishlist
{
    public function __construct(
        private WishlistRepository $repository,
    ) {
    }

    public function handle(RemoveFromWishlistCommand $command): void
    {
        $this->repository->remove($command->owner, $command->accommodationId);
    }
}
