<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Application\UseCase;

use App\SolidarityProject\Application\UseCase\SetSolidarityProjectStatus;
use App\SolidarityProject\Domain\Command\SetSolidarityProjectStatusCommand;
use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Exception\SolidarityProjectNotFoundException;
use App\Tests\Unit\SolidarityProject\Infrastructure\InMemorySolidarityProjectRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SetSolidarityProjectStatusTest extends TestCase
{
    private InMemorySolidarityProjectRepository $repository;
    private SetSolidarityProjectStatus $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemorySolidarityProjectRepository();
        $this->useCase = new SetSolidarityProjectStatus($this->repository);
    }

    public function test_should_change_status_while_preserving_other_fields(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $createdAt = new \DateTimeImmutable('2026-01-01T10:00:00+00:00');
        $this->repository->save(new SolidarityProject(
            id: $id,
            title: 'Reforestation',
            description: 'Plant 10 000 trees.',
            imageUrl: 'https://example.com/img.jpg',
            status: SolidarityProject::STATUS_ACTIVE,
            createdAt: $createdAt,
            isDefault: true,
            keyFigures: [new KeyFigure('10 000', 'arbres')],
        ));

        $this->useCase->handle(new SetSolidarityProjectStatusCommand(
            projectId: $id,
            status: SolidarityProject::STATUS_CLOSED,
        ));

        $project = $this->repository->findById($id);
        self::assertInstanceOf(SolidarityProject::class, $project);
        self::assertSame(SolidarityProject::STATUS_CLOSED, $project->getStatus());
        self::assertTrue($project->isDefault());
        self::assertEquals($createdAt, $project->getCreatedAt());
        self::assertCount(1, $project->getKeyFigures());
    }

    public function test_should_throw_not_found_when_project_does_not_exist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(SolidarityProjectNotFoundException::class);

        $this->useCase->handle(new SetSolidarityProjectStatusCommand(
            projectId: $id,
            status: SolidarityProject::STATUS_CLOSED,
        ));
    }
}
