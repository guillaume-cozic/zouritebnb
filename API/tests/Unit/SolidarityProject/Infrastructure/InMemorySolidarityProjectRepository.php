<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Infrastructure;

use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Port\SolidarityProjectRepository;
use Symfony\Component\Uid\Uuid;

final class InMemorySolidarityProjectRepository implements SolidarityProjectRepository
{
    /** @var array<string, SolidarityProject> */
    private array $projects = [];

    /** @var string[] */
    public array $markedAsDefault = [];

    public function save(SolidarityProject $project): void
    {
        $this->projects[$project->getId()->toRfc4122()] = $project;
    }

    public function findById(Uuid $id): ?SolidarityProject
    {
        return $this->projects[$id->toRfc4122()] ?? null;
    }

    public function findAllActive(): array
    {
        return array_values(array_filter(
            $this->projects,
            static fn (SolidarityProject $project): bool => SolidarityProject::STATUS_ACTIVE === $project->getStatus(),
        ));
    }

    public function markAsDefault(Uuid $id): void
    {
        $this->markedAsDefault[] = $id->toRfc4122();
    }
}
