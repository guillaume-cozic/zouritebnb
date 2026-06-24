<?php

declare(strict_types=1);

namespace App\SolidarityProject\Application\UseCase;

use App\SolidarityProject\Domain\Command\UpdateSolidarityProjectCommand;
use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Entity\ProjectTranslation;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Exception\SolidarityProjectNotFoundException;
use App\SolidarityProject\Domain\Port\SolidarityProjectRepository;

final readonly class UpdateSolidarityProject
{
    public function __construct(private SolidarityProjectRepository $repository)
    {
    }

    public function handle(UpdateSolidarityProjectCommand $command): void
    {
        $project = $this->repository->findById($command->projectId);

        if (null === $project) {
            throw SolidarityProjectNotFoundException::becauseNotFound($command->projectId->toRfc4122());
        }

        // Rebuild the immutable aggregate with the edited values, preserving the
        // creation date and the platform-default ("coup de cœur") flag.
        $updated = new SolidarityProject(
            id: $project->getId(),
            translations: $this->buildTranslations($command->translations),
            imageUrl: $command->imageUrl,
            status: $command->status,
            createdAt: $project->getCreatedAt(),
            isDefault: $project->isDefault(),
        );

        $this->repository->save($updated);
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
