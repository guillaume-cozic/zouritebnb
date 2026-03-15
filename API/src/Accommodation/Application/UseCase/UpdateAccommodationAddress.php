<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\UpdateAccommodationAddressCommand;
use App\Accommodation\Domain\Entity\Address;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class UpdateAccommodationAddress
{
    public function __construct(
        private AccommodationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UpdateAccommodationAddressCommand $command): void
    {
        $accommodation = $this->repository->findById($command->id);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->id->toRfc4122());
        }

        $address = new Address(
            street: $command->street ?? '',
            city: $command->city ?? '',
            zipCode: $command->zipCode ?? '',
            country: $command->country ?? '',
        );

        $accommodation->updateAddress($address);
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
