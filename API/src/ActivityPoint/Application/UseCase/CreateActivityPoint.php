<?php

declare(strict_types=1);

namespace App\ActivityPoint\Application\UseCase;

use App\ActivityPoint\Domain\Command\CreateActivityPointCommand;
use App\ActivityPoint\Domain\Entity\ActivityPoint;
use App\ActivityPoint\Domain\Entity\ActivityPointCategory;
use App\ActivityPoint\Domain\Entity\Coordinates;
use App\ActivityPoint\Domain\Exception\InvalidActivityPointException;
use App\ActivityPoint\Domain\Port\ActivityPointRepository;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class CreateActivityPoint
{
    public function __construct(private ActivityPointRepository $repository)
    {
    }

    public function handle(CreateActivityPointCommand $command): void
    {
        $point = new ActivityPoint(
            id: UuidGenerator::generate(),
            name: $command->name,
            description: $command->description,
            category: ActivityPointCategory::tryFrom($command->category) ?? throw InvalidActivityPointException::becauseCategoryIsInvalid($command->category),
            coordinates: new Coordinates($command->latitude, $command->longitude),
            articleUrl: $command->articleUrl,
        );

        $this->repository->save($point);
    }
}
