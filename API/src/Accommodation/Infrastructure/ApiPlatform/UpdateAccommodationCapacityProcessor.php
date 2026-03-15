<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationCapacity;
use App\Accommodation\Domain\Command\UpdateAccommodationCapacityCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationCapacityInput, void>
 */
final readonly class UpdateAccommodationCapacityProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationCapacity $updateAccommodationCapacity,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->handler->execute(fn () => $this->updateAccommodationCapacity->handle(new UpdateAccommodationCapacityCommand(
            id: Uuid::fromString($uriVariables['id']),
            bedrooms: $data->bedrooms,
            bathrooms: $data->bathrooms,
            maxGuests: $data->maxGuests,
            singleBeds: $data->singleBeds,
            doubleBeds: $data->doubleBeds,
        )));
    }
}
