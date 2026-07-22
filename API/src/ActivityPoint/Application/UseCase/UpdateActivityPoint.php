<?php

declare(strict_types=1);

namespace App\ActivityPoint\Application\UseCase;

use App\ActivityPoint\Domain\Command\UpdateActivityPointCommand;
use App\ActivityPoint\Domain\Entity\ActivityPoint;
use App\ActivityPoint\Domain\Entity\ActivityPointCategory;
use App\ActivityPoint\Domain\Entity\Coordinates;
use App\ActivityPoint\Domain\Exception\ActivityPointNotFoundException;
use App\ActivityPoint\Domain\Exception\InvalidActivityPointException;
use App\ActivityPoint\Domain\Port\ActivityPointRepository;

final readonly class UpdateActivityPoint
{
    public function __construct(private ActivityPointRepository $repository)
    {
    }

    public function handle(UpdateActivityPointCommand $command): void
    {
        $point = $this->repository->findById($command->id);

        if (null === $point) {
            throw ActivityPointNotFoundException::becauseNotFound($command->id->toRfc4122());
        }

        // Rebuild the immutable aggregate with the edited values.
        $updated = new ActivityPoint(
            id: $point->getId(),
            name: $command->name,
            description: $command->description,
            category: ActivityPointCategory::tryFrom($command->category) ?? throw InvalidActivityPointException::becauseCategoryIsInvalid($command->category),
            coordinates: new Coordinates($command->latitude, $command->longitude),
            articleUrl: $command->articleUrl,
        );

        $this->repository->save($updated);
    }
}
