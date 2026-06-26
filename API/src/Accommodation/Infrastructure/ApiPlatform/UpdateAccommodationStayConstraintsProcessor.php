<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationStayConstraints;
use App\Accommodation\Domain\Command\UpdateAccommodationStayConstraintsCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationStayConstraintsInput, void>
 */
final readonly class UpdateAccommodationStayConstraintsProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationStayConstraints $updateAccommodationStayConstraints,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof UpdateAccommodationStayConstraintsInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', UpdateAccommodationStayConstraintsInput::class, get_debug_type($data)));
        }

        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationStayConstraints->handle(new UpdateAccommodationStayConstraintsCommand(
            accommodationId: $id,
            minNights: $data->minNights,
            maxNights: $data->maxNights,
        )));
    }
}
