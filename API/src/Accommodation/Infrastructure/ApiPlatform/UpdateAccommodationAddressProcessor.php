<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationAddress;
use App\Accommodation\Domain\Command\UpdateAccommodationAddressCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationAddressInput, void>
 */
final readonly class UpdateAccommodationAddressProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationAddress $updateAccommodationAddress,
        private TransactionalUseCaseHandler $handler,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $this->handler->execute(fn () => $this->updateAccommodationAddress->handle(new UpdateAccommodationAddressCommand(
            id: Uuid::fromString($uriVariables['id']),
            street: $data->street,
            city: $data->city,
            zipCode: $data->zipCode,
            country: $data->country,
        )));
    }
}
