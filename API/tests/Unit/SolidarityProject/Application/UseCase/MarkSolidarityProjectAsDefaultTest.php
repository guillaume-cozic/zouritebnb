<?php

declare(strict_types=1);

namespace App\Tests\Unit\SolidarityProject\Application\UseCase;

use App\SolidarityProject\Application\UseCase\MarkSolidarityProjectAsDefault;
use App\SolidarityProject\Domain\Command\MarkSolidarityProjectAsDefaultCommand;
use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Exception\SolidarityProjectNotFoundException;
use App\Tests\Unit\SolidarityProject\Infrastructure\InMemorySolidarityProjectRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class MarkSolidarityProjectAsDefaultTest extends TestCase
{
    private InMemorySolidarityProjectRepository $repository;
    private MarkSolidarityProjectAsDefault $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemorySolidarityProjectRepository();
        $this->useCase = new MarkSolidarityProjectAsDefault($this->repository);
    }

    public function test_should_mark_existing_project_as_default(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenProject($id);

        $this->useCase->handle(new MarkSolidarityProjectAsDefaultCommand(projectId: $id));

        self::assertSame([$id->toRfc4122()], $this->repository->markedAsDefault);
    }

    public function test_should_throw_not_found_when_project_does_not_exist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(SolidarityProjectNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Solidarity project "%s" was not found.', $id->toRfc4122()));

        $this->useCase->handle(new MarkSolidarityProjectAsDefaultCommand(projectId: $id));
    }

    public function test_should_not_mark_as_default_when_project_does_not_exist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        try {
            $this->useCase->handle(new MarkSolidarityProjectAsDefaultCommand(projectId: $id));
            self::fail('Expected SolidarityProjectNotFoundException to be thrown.');
        } catch (SolidarityProjectNotFoundException) {
            self::assertSame([], $this->repository->markedAsDefault);
        }
    }

    private function givenProject(Uuid $id): void
    {
        $this->repository->save(new SolidarityProject(
            id: $id,
            title: 'Help refugees',
            description: 'A solidarity project to help refugees.',
            imageUrl: null,
            status: SolidarityProject::STATUS_ACTIVE,
        ));
    }
}
