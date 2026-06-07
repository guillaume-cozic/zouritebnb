<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Accommodation\Application\UseCase\UpdateAccommodationDescription;
use App\Accommodation\Domain\Command\UpdateAccommodationDescriptionCommand;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UpdateAccommodationDescriptionInput, void>
 */
final readonly class UpdateAccommodationDescriptionProcessor implements ProcessorInterface
{
    public function __construct(
        private UpdateAccommodationDescription $updateAccommodationDescription,
        private AccommodationOwnershipGuard $ownershipGuard,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert($data instanceof UpdateAccommodationDescriptionInput);

        $id = Uuid::fromString($uriVariables['id']);
        $this->ownershipGuard->assertOwnedByCurrentUser($id);

        $this->updateAccommodationDescription->handle(new UpdateAccommodationDescriptionCommand(
            id: $id,
            title: $data->title,
            description: $data->description,
        ));
    }
}
