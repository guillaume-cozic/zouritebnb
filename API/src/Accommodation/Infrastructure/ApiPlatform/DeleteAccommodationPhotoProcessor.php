<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\DeleteAccommodationPhoto;
use App\Accommodation\Domain\Command\DeleteAccommodationPhotoCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<mixed, void>
 */
final readonly class DeleteAccommodationPhotoProcessor implements ProcessorInterface
{
    public function __construct(
        private DeleteAccommodationPhoto $deleteAccommodationPhoto,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $accommodationId = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($accommodationId);

        $this->handler->execute(fn () => $this->deleteAccommodationPhoto->handle(new DeleteAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            photoId: Uuid::fromString($uriVariables['photoId']),
        )));
    }
}
