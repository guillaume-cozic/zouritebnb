<?php

declare(strict_types=1);

namespace App\SolidarityProject\Application\UseCase;

use App\SolidarityProject\Domain\Command\MarkSolidarityProjectAsDefaultCommand;
use App\SolidarityProject\Domain\Exception\SolidarityProjectNotFoundException;
use App\SolidarityProject\Domain\Port\SolidarityProjectRepository;

final readonly class MarkSolidarityProjectAsDefault
{
    public function __construct(private SolidarityProjectRepository $repository)
    {
    }

    public function handle(MarkSolidarityProjectAsDefaultCommand $command): void
    {
        $project = $this->repository->findById($command->projectId);

        if (null === $project) {
            throw SolidarityProjectNotFoundException::becauseNotFound($command->projectId->toRfc4122());
        }

        $this->repository->markAsDefault($command->projectId);
    }
}
