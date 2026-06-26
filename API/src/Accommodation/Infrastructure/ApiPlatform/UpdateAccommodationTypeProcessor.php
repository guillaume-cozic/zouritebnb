<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationType;
use App\Accommodation\Domain\Command\UpdateAccommodationTypeCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationTypeInput, void>
 */
final readonly class UpdateAccommodationTypeProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationType $updateAccommodationType,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof UpdateAccommodationTypeInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', UpdateAccommodationTypeInput::class, get_debug_type($data)));
        }

        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationType->handle(new UpdateAccommodationTypeCommand(
            accommodationId: $id,
            type: $data->type,
        )));
    }
}
