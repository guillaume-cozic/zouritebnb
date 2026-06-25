<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationCancellationPolicy;
use App\Accommodation\Domain\Command\UpdateAccommodationCancellationPolicyCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationCancellationPolicyInput, void>
 */
final readonly class UpdateAccommodationCancellationPolicyProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationCancellationPolicy $updateAccommodationCancellationPolicy,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof UpdateAccommodationCancellationPolicyInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', UpdateAccommodationCancellationPolicyInput::class, get_debug_type($data)));
        }

        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationCancellationPolicy->handle(new UpdateAccommodationCancellationPolicyCommand(
            accommodationId: $id,
            cancellationPolicy: $data->cancellationPolicy,
        )));
    }
}
