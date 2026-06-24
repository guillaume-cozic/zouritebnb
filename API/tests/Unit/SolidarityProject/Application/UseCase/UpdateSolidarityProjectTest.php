<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Application\UseCase;

use App\SolidarityProject\Application\UseCase\UpdateSolidarityProject;
use App\SolidarityProject\Domain\Command\UpdateSolidarityProjectCommand;
use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Entity\ProjectTranslation;
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
            translations: ['fr' => new ProjectTranslation('Ancien titre', 'Ancienne description', [new KeyFigure('1', 'a')])],
            imageUrl: null,
            status: SolidarityProject::STATUS_ACTIVE,
            createdAt: $createdAt,
            isDefault: true,
        ));

        $this->useCase->handle(new UpdateSolidarityProjectCommand(
            projectId: $id,
            translations: [
                'fr' => ['title' => 'Nouveau titre', 'description' => 'Nouvelle description', 'keyFigures' => [['value' => '5 ans', 'label' => 'de programme']]],
                'en' => ['title' => 'New title', 'description' => 'New description', 'keyFigures' => []],
            ],
            imageUrl: 'https://example.com/img.jpg',
            status: SolidarityProject::STATUS_CLOSED,
        ));

        $project = $this->repository->findById($id);
        self::assertInstanceOf(SolidarityProject::class, $project);
        self::assertSame('Nouveau titre', $project->translation('fr')->getTitle());
        self::assertSame('Nouvelle description', $project->translation('fr')->getDescription());
        self::assertSame('New title', $project->translation('en')->getTitle());
        self::assertSame('https://example.com/img.jpg', $project->getImageUrl());
        self::assertSame(SolidarityProject::STATUS_CLOSED, $project->getStatus());
        self::assertTrue($project->isDefault());
        self::assertEquals($createdAt, $project->getCreatedAt());
        self::assertCount(1, $project->translation('fr')->getKeyFigures());
        self::assertSame('de programme', $project->translation('fr')->getKeyFigures()[0]->label());
    }

    public function test_should_throw_not_found_when_project_does_not_exist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(SolidarityProjectNotFoundException::class);

        $this->useCase->handle(new UpdateSolidarityProjectCommand(
            projectId: $id,
            translations: ['fr' => ['title' => 'Titre', 'description' => 'Description', 'keyFigures' => []]],
            imageUrl: null,
            status: SolidarityProject::STATUS_ACTIVE,
        ));
    }
}
