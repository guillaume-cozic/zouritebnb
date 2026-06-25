<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Wishlist\Application\UseCase\RemoveFromWishlist;
use App\Wishlist\Domain\Command\RemoveFromWishlistCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class RemoveWishlistItemProcessor implements ProcessorInterface
{
    public function __construct(
        private RemoveFromWishlist $removeFromWishlist,
        private WishlistOwnerResolver $ownerResolver,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $owner = $this->ownerResolver->resolve();
        $rawAccommodationId = (string) ($uriVariables['accommodationId'] ?? '');

        // Anonymous without a correlation id, or a malformed id: nothing to remove.
        if (null === $owner || !Uuid::isValid($rawAccommodationId)) {
            return;
        }

        $this->removeFromWishlist->handle(new RemoveFromWishlistCommand(
            owner: $owner,
            accommodationId: Uuid::fromString($rawAccommodationId),
        ));
    }
}
