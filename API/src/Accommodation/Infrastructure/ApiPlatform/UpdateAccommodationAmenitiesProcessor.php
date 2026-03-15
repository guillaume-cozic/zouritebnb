<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationAmenities;
use App\Accommodation\Domain\Command\UpdateAccommodationAmenitiesCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationAmenitiesInput, void>
 */
final readonly class UpdateAccommodationAmenitiesProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationAmenities $updateAccommodationAmenities,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->handler->execute(fn () => $this->updateAccommodationAmenities->handle(new UpdateAccommodationAmenitiesCommand(
            id: Uuid::fromString($uriVariables['id']),
            codes: $data->codes ?? [],
        )));
    }
}
