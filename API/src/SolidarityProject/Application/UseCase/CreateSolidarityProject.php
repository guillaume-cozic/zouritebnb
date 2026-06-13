<?php

declare(strict_types=1);

namespace App\SolidarityProject\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\SolidarityProject\Domain\Command\CreateSolidarityProjectCommand;
use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Port\SolidarityProjectRepository;

final readonly class CreateSolidarityProject
{
    public function __construct(private SolidarityProjectRepository $repository)
    {
    }

    public function handle(CreateSolidarityProjectCommand $command): string
    {
        $keyFigures = array_map(
            static fn (array $keyFigure): KeyFigure => new KeyFigure($keyFigure['value'] ?? null, $keyFigure['label'] ?? null),
            $command->keyFigures,
        );

        $project = new SolidarityProject(
            id: UuidGenerator::generate(),
            title: $command->title,
            description: $command->description,
            imageUrl: $command->imageUrl,
            status: $command->status,
            keyFigures: $keyFigures,
        );

        $this->repository->save($project);

        return $project->getId()->toRfc4122();
    }
}
