<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Infrastructure\Security\CurrentUser;
use App\Wishlist\Application\UseCase\MergeAnonymousWishlist;

/**
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class MergeWishlistProcessor implements ProcessorInterface
{
    public function __construct(
        private MergeAnonymousWishlist $mergeAnonymousWishlist,
        private WishlistOwnerResolver $ownerResolver,
        private CurrentUser $currentUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $correlationId = $this->ownerResolver->correlationId();
        if (null === $correlationId) {
            // No anonymous wishlist to merge.
            return;
        }

        $this->mergeAnonymousWishlist->handle($correlationId, $this->currentUser->id());
    }
}
