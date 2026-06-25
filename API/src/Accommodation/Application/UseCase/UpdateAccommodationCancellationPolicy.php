<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\UpdateAccommodationCancellationPolicyCommand;
use App\Accommodation\Domain\Entity\CancellationPolicy;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class UpdateAccommodationCancellationPolicy
{
    public function __construct(
        private AccommodationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UpdateAccommodationCancellationPolicyCommand $command): void
    {
        $accommodation = $this->repository->findById($command->accommodationId);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->accommodationId->toRfc4122());
        }

        $accommodation->updateCancellationPolicy(CancellationPolicy::fromString($command->cancellationPolicy));
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
