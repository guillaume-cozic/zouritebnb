<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationCheckInOut;
use App\Accommodation\Domain\Command\UpdateAccommodationCheckInOutCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationCheckInOutInput, void>
 */
final readonly class UpdateAccommodationCheckInOutProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationCheckInOut $updateAccommodationCheckInOut,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof UpdateAccommodationCheckInOutInput);

        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationCheckInOut->handle(new UpdateAccommodationCheckInOutCommand(
            id: $id,
            checkIn: $data->checkIn,
            checkOut: $data->checkOut,
        )));
    }
}
