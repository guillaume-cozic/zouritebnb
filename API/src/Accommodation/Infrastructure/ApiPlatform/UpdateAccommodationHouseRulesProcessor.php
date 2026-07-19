<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationHouseRules;
use App\Accommodation\Domain\Command\UpdateAccommodationHouseRulesCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationHouseRulesInput, void>
 */
final readonly class UpdateAccommodationHouseRulesProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationHouseRules $updateAccommodationHouseRules,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof UpdateAccommodationHouseRulesInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', UpdateAccommodationHouseRulesInput::class, get_debug_type($data)));
        }

        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationHouseRules->handle(new UpdateAccommodationHouseRulesCommand(
            accommodationId: $id,
            smokingAllowed: $data->smokingAllowed,
            petsAllowed: $data->petsAllowed,
            partiesAllowed: $data->partiesAllowed,
            houseRulesNotes: $data->houseRulesNotes,
        )));
    }
}
