<?php

declare(strict_types=1);

namespace App\Tests\Unit\ActivityPoint\Application\UseCase;

use App\ActivityPoint\Application\UseCase\DeleteActivityPoint;
use App\ActivityPoint\Domain\Command\DeleteActivityPointCommand;
use App\ActivityPoint\Domain\Entity\ActivityPoint;
use App\ActivityPoint\Domain\Entity\ActivityPointCategory;
use App\ActivityPoint\Domain\Entity\Coordinates;
use App\ActivityPoint\Domain\Exception\ActivityPointNotFoundException;
use App\Tests\Unit\ActivityPoint\Infrastructure\InMemoryActivityPointRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DeleteActivityPointTest extends TestCase
{
    private InMemoryActivityPointRepository $repository;
    private DeleteActivityPoint $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryActivityPointRepository();
        $this->useCase = new DeleteActivityPoint($this->repository);
    }

    public function test_should_remove_an_existing_activity_point(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new ActivityPoint(
            id: $id,
            name: 'Trou d\'Argent',
            description: 'One of the most beautiful beaches of Rodrigues.',
            category: ActivityPointCategory::Beach,
            coordinates: new Coordinates(-19.7245, 63.4842),
            articleUrl: null,
        ));

        $this->useCase->handle(new DeleteActivityPointCommand(id: $id));

        self::assertNull($this->repository->findById($id));
    }

    public function test_should_throw_not_found_when_point_does_not_exist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(ActivityPointNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Activity point "%s" was not found.', $id->toRfc4122()));

        $this->useCase->handle(new DeleteActivityPointCommand(id: $id));
    }
}
