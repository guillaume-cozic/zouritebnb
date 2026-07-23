<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationExtraServices;
use App\Accommodation\Domain\Command\UpdateAccommodationExtraServicesCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationExtraServicesInput, void>
 */
final readonly class UpdateAccommodationExtraServicesProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationExtraServices $updateAccommodationExtraServices,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof UpdateAccommodationExtraServicesInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', UpdateAccommodationExtraServicesInput::class, get_debug_type($data)));
        }

        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationExtraServices->handle(new UpdateAccommodationExtraServicesCommand(
            accommodationId: $id,
            extraServices: $data->extraServices,
        )));
    }
}
