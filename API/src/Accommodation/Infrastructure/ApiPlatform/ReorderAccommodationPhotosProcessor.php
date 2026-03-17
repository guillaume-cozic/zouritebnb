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
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof ReorderAccommodationPhotosInput);

        $this->reorderAccommodationPhotos->handle(new ReorderAccommodationPhotosCommand(
            accommodationId: Uuid::fromString($uriVariables['id']),
            photoIds: array_map(static fn (string $id) => Uuid::fromString($id), $data->photoIds),
        ));
    }
}
