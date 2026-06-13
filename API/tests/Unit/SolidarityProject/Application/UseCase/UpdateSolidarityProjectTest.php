<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Application\UseCase;

use App\SolidarityProject\Application\UseCase\UpdateSolidarityProject;
use App\SolidarityProject\Domain\Command\UpdateSolidarityProjectCommand;
use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Exception\SolidarityProjectNotFoundException;
use App\Tests\Unit\SolidarityProject\Infrastructure\InMemorySolidarityProjectRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateSolidarityProjectTest extends TestCase
{
    private InMemorySolidarityProjectRepository $repository;
    private UpdateSolidarityProject $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemorySolidarityProjectRepository();
        $this->useCase = new UpdateSolidarityProject($this->repository);
    }

    public function test_should_update_editable_fields_and_preserve_created_at_and_default_flag(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $createdAt = new \DateTimeImmutable('2026-01-01T10:00:00+00:00');
        $this->repository->save(new SolidarityProject(
            id: $id,
            title: 'Ancien titre',
            description: 'Ancienne description',
            imageUrl: null,
            status: SolidarityProject::STATUS_ACTIVE,
            createdAt: $createdAt,
            isDefault: true,
            keyFigures: [new KeyFigure('1', 'a')],
        ));

        $this->useCase->handle(new UpdateSolidarityProjectCommand(
            projectId: $id,
            title: 'Nouveau titre',
            description: 'Nouvelle description',
            imageUrl: 'https://example.com/img.jpg',
            status: SolidarityProject::STATUS_CLOSED,
            keyFigures: [['value' => '5 ans', 'label' => 'de programme']],
        ));

        $project = $this->repository->findById($id);
        self::assertInstanceOf(SolidarityProject::class, $project);
        self::assertSame('Nouveau titre', $project->getTitle());
        self::assertSame('Nouvelle description', $project->getDescription());
        self::assertSame('https://example.com/img.jpg', $project->getImageUrl());
        self::assertSame(SolidarityProject::STATUS_CLOSED, $project->getStatus());
        self::assertTrue($project->isDefault());
        self::assertEquals($createdAt, $project->getCreatedAt());
        self::assertCount(1, $project->getKeyFigures());
        self::assertSame('de programme', $project->getKeyFigures()[0]->label());
    }

    public function test_should_throw_not_found_when_project_does_not_exist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(SolidarityProjectNotFoundException::class);

        $this->useCase->handle(new UpdateSolidarityProjectCommand(
            projectId: $id,
            title: 'Titre',
            description: 'Description',
            imageUrl: null,
            status: SolidarityProject::STATUS_ACTIVE,
        ));
    }
}
