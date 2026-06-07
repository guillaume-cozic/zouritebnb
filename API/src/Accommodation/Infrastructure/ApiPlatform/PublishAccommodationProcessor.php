<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\PublishAccommodation;
use App\Accommodation\Domain\Command\PublishAccommodationCommand;
use App\Shared\Infrastructure\TransactionalUseCaseHandler;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<AccommodationOutput, void>
 */
final readonly class PublishAccommodationProcessor implements ProcessorInterface
{
    public function __construct(
        private PublishAccommodation $publishAccommodation,
        private TransactionalUseCaseHandler $handler,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->handler->execute(fn () => $this->publishAccommodation->handle(new PublishAccommodationCommand(
            id: $id,
        )));
    }
}
