<?php

declare(strict_types=1);

namespace App\SolidarityProject\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\SolidarityProject\Domain\Command\CreateSolidarityProjectCommand;
use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Entity\ProjectTranslation;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Port\SolidarityProjectRepository;

final readonly class CreateSolidarityProject
{
    public function __construct(private SolidarityProjectRepository $repository)
    {
    }

    public function handle(CreateSolidarityProjectCommand $command): string
    {
        $project = new SolidarityProject(
            id: UuidGenerator::generate(),
            translations: $this->buildTranslations($command->translations),
            imageUrl: $command->imageUrl,
            status: $command->status,
        );

        $this->repository->save($project);

        return $project->getId()->toRfc4122();
    }

    /**
     * @param array<string, array{title: string, description: string, keyFigures: array<array{value: string|null, label: string|null}>}> $translations
     *
     * @return array<string, ProjectTranslation>
     */
    private function buildTranslations(array $translations): array
    {
        $built = [];
        foreach ($translations as $locale => $translation) {
            $keyFigures = array_map(
                static fn (array $keyFigure): KeyFigure => new KeyFigure($keyFigure['value'] ?? null, $keyFigure['label'] ?? null),
                $translation['keyFigures'] ?? [],
            );

            $built[$locale] = new ProjectTranslation(
                $translation['title'] ?? '',
                $translation['description'] ?? '',
                $keyFigures,
            );
        }

        return $built;
    }
}
