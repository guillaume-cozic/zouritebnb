<?php

declare(strict_types=1);

namespace App\Wishlist\Application\UseCase;

use App\Shared\Domain\Port\AccommodationSummaryProvider;
use App\Shared\Domain\Port\UuidGenerator;
use App\Wishlist\Domain\Command\AddToWishlistCommand;
use App\Wishlist\Domain\Entity\WishlistItem;
use App\Wishlist\Domain\Exception\WishlistException;
use App\Wishlist\Domain\Port\WishlistRepository;

final readonly class AddToWishlist
{
    public function __construct(
        private WishlistRepository $repository,
        private AccommodationSummaryProvider $accommodationSummaryProvider,
    ) {
    }

    public function handle(AddToWishlistCommand $command): void
    {
        if (null === $this->accommodationSummaryProvider->summaryOf($command->accommodationId)) {
            throw WishlistException::becauseAccommodationNotFound();
        }

        // Idempotent: saving an accommodation already on the wishlist is a no-op.
        if ($this->repository->exists($command->owner, $command->accommodationId)) {
            return;
        }

        $this->repository->add(new WishlistItem(
            id: UuidGenerator::generate(),
            owner: $command->owner,
            accommodationId: $command->accommodationId,
        ));
    }
}
