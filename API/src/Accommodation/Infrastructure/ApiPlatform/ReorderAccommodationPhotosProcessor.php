<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\ReorderAccommodationPhotos;
use App\Accommodation\Domain\Command\ReorderAccommodationPhotosCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<ReorderAccommodationPhotosInput, void>
 */
final readonly class ReorderAccommodationPhotosProcessor implements ProcessorInterface
{
    public function __construct(
        private ReorderAccommodationPhotos $reorderAccommodationPhotos,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof ReorderAccommodationPhotosInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', ReorderAccommodationPhotosInput::class, get_debug_type($data)));
        }

        $accommodationId = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($accommodationId);

        $this->reorderAccommodationPhotos->handle(new ReorderAccommodationPhotosCommand(
            accommodationId: $accommodationId,
            photoIds: array_map(static fn (string $id) => Uuid::fromString($id), $data->photoIds),
        ));
    }
}
