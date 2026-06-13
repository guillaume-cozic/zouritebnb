<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\SolidarityProject\Application\UseCase\CreateSolidarityProject;
use App\SolidarityProject\Domain\Command\CreateSolidarityProjectCommand;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Exception\InvalidSolidarityProjectException;
use App\Tests\Unit\SolidarityProject\Infrastructure\InMemorySolidarityProjectRepository;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateSolidarityProjectTest extends TestCase
{
    private InMemorySolidarityProjectRepository $repository;
    private CreateSolidarityProject $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemorySolidarityProjectRepository();
        $this->useCase = new CreateSolidarityProject($this->repository);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_create_and_persist_an_active_project_with_key_figures(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        UuidGenerator::freeze($id);

        $returnedId = $this->useCase->handle(new CreateSolidarityProjectCommand(
            title: 'Reforestation',
            description: 'Plant 10 000 trees.',
            imageUrl: 'https://example.com/img.jpg',
            status: SolidarityProject::STATUS_ACTIVE,
            keyFigures: [['value' => '10 000', 'label' => 'arbres']],
        ));

        self::assertSame($id->toRfc4122(), $returnedId);

        $project = $this->repository->findById($id);
        self::assertInstanceOf(SolidarityProject::class, $project);
        self::assertSame('Reforestation', $project->getTitle());
        self::assertSame(SolidarityProject::STATUS_ACTIVE, $project->getStatus());
        self::assertFalse($project->isDefault());
        self::assertCount(1, $project->getKeyFigures());
        self::assertSame('arbres', $project->getKeyFigures()[0]->label());
    }

    public function test_should_throw_when_status_is_invalid(): void
    {
        $this->expectException(InvalidSolidarityProjectException::class);

        $this->useCase->handle(new CreateSolidarityProjectCommand(
            title: 'Reforestation',
            description: 'Plant 10 000 trees.',
            imageUrl: null,
            status: 'paused',
        ));
    }
}
