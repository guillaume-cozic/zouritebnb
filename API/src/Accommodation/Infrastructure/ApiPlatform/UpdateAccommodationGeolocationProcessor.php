<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationGeolocation;
use App\Accommodation\Domain\Command\UpdateAccommodationGeolocationCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationGeolocationInput, void>
 */
final readonly class UpdateAccommodationGeolocationProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationGeolocation $updateAccommodationGeolocation,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationGeolocation->handle(new UpdateAccommodationGeolocationCommand(
            id: $id,
            latitude: $data->latitude,
            longitude: $data->longitude,
        )));
    }
}
