<?php

declare(strict_types=1);

namespace App\Accommodation\Application\UseCase;

use App\Accommodation\Domain\Command\UpdateAccommodationWeeklyPromotionCommand;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Port\AccommodationRepository;
use App\Shared\Domain\Port\EventBus;

final readonly class UpdateAccommodationWeeklyPromotion
{
    public function __construct(
        private AccommodationRepository $repository,
        private EventBus $eventBus,
    ) {
    }

    public function handle(UpdateAccommodationWeeklyPromotionCommand $command): void
    {
        $accommodation = $this->repository->findById($command->accommodationId);

        if (null === $accommodation) {
            throw AccommodationNotFoundException::becauseNotFound($command->accommodationId->toRfc4122());
        }

        $accommodation->updateWeeklyPromotion($command->weeklyPromotionPercentage);
        $this->repository->save($accommodation);

        $this->eventBus->dispatch($accommodation->releaseEvents());
    }
}
