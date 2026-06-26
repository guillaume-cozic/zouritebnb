<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationInstantBooking;
use App\Accommodation\Domain\Command\UpdateAccommodationInstantBookingCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationInstantBookingInput, void>
 */
final readonly class UpdateAccommodationInstantBookingProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationInstantBooking $updateAccommodationInstantBooking,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof UpdateAccommodationInstantBookingInput) {
            throw new \InvalidArgumentException(\sprintf('Expected "%s", got "%s".', UpdateAccommodationInstantBookingInput::class, get_debug_type($data)));
        }

        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->updateAccommodationInstantBooking->handle(new UpdateAccommodationInstantBookingCommand(
            accommodationId: $id,
            instantBooking: (bool) $data->instantBooking,
        )));
    }
}
