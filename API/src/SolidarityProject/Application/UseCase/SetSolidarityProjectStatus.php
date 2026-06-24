<?php

declare(strict_types=1);

namespace App\SolidarityProject\Application\UseCase;

use App\SolidarityProject\Domain\Command\SetSolidarityProjectStatusCommand;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Exception\SolidarityProjectNotFoundException;
use App\SolidarityProject\Domain\Port\SolidarityProjectRepository;

final readonly class SetSolidarityProjectStatus
{
    public function __construct(private SolidarityProjectRepository $repository)
    {
    }

    public function handle(SetSolidarityProjectStatusCommand $command): void
    {
        $project = $this->repository->findById($command->projectId);

        if (null === $project) {
            throw SolidarityProjectNotFoundException::becauseNotFound($command->projectId->toRfc4122());
        }

        // Rebuild the immutable aggregate with the new status, preserving the
        // creation date, the platform-default flag and every translation.
        $updated = new SolidarityProject(
            id: $project->getId(),
            translations: $project->getTranslations(),
            imageUrl: $project->getImageUrl(),
            status: $command->status,
            createdAt: $project->getCreatedAt(),
            isDefault: $project->isDefault(),
        );

        $this->repository->save($updated);
    }
}
