<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Wishlist\Application\UseCase\AddToWishlist;
use App\Wishlist\Domain\Command\AddToWishlistCommand;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<AddWishlistItemInput, WishlistItemOutput>
 */
final readonly class AddWishlistItemProcessor implements ProcessorInterface
{
    public function __construct(
        private AddToWishlist $addToWishlist,
        private WishlistOwnerResolver $ownerResolver,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WishlistItemOutput
    {
        if (!$data instanceof AddWishlistItemInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', AddWishlistItemInput::class, get_debug_type($data)));
        }

        $owner = $this->ownerResolver->resolve();
        if (null === $owner) {
            throw new BadRequestHttpException('A wishlist correlation id (header '.WishlistOwnerResolver::CORRELATION_HEADER.') is required for anonymous visitors.');
        }

        $this->addToWishlist->handle(new AddToWishlistCommand(
            owner: $owner,
            accommodationId: Uuid::fromString($data->accommodationId),
        ));

        $output = new WishlistItemOutput();
        $output->accommodationId = $data->accommodationId;

        return $output;
    }
}
