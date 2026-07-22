<?php

declare(strict_types=1);

namespace App\Tests\Unit\ActivityPoint\Application\UseCase;

use App\ActivityPoint\Application\UseCase\UpdateActivityPoint;
use App\ActivityPoint\Domain\Command\UpdateActivityPointCommand;
use App\ActivityPoint\Domain\Entity\ActivityPoint;
use App\ActivityPoint\Domain\Entity\ActivityPointCategory;
use App\ActivityPoint\Domain\Entity\Coordinates;
use App\ActivityPoint\Domain\Exception\ActivityPointNotFoundException;
use App\ActivityPoint\Domain\Exception\InvalidActivityPointException;
use App\Tests\Unit\ActivityPoint\Infrastructure\InMemoryActivityPointRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateActivityPointTest extends TestCase
{
    private InMemoryActivityPointRepository $repository;
    private UpdateActivityPoint $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryActivityPointRepository();
        $this->useCase = new UpdateActivityPoint($this->repository);
    }

    public function test_should_update_all_fields(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new ActivityPoint(
            id: $id,
            name: 'Old name',
            description: 'Old description.',
            category: ActivityPointCategory::Nature,
            coordinates: new Coordinates(-19.7, 63.4),
            articleUrl: null,
        ));

        $this->useCase->handle(new UpdateActivityPointCommand(
            id: $id,
            name: 'Passe Demie',
            description: 'World-class kitesurf spot.',
            category: 'kitesurf',
            latitude: -19.7583,
            longitude: 63.4176,
            articleUrl: 'https://example.com/passe-demie',
        ));

        $point = $this->repository->findById($id);
        self::assertInstanceOf(ActivityPoint::class, $point);
        self::assertSame('Passe Demie', $point->getName());
        self::assertSame('World-class kitesurf spot.', $point->getDescription());
        self::assertSame(ActivityPointCategory::Kitesurf, $point->getCategory());
        self::assertSame(-19.7583, $point->getCoordinates()->latitude());
        self::assertSame(63.4176, $point->getCoordinates()->longitude());
        self::assertSame('https://example.com/passe-demie', $point->getArticleUrl());
    }

    public function test_should_throw_not_found_when_point_does_not_exist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(ActivityPointNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Activity point "%s" was not found.', $id->toRfc4122()));

        $this->useCase->handle(new UpdateActivityPointCommand(
            id: $id,
            name: 'Passe Demie',
            description: 'World-class kitesurf spot.',
            category: 'kitesurf',
            latitude: -19.7583,
            longitude: 63.4176,
            articleUrl: null,
        ));
    }

    /**
     * @return \Generator<string, array{string, string, string, float|null, float|null, string|null, string}>
     */
    public static function invalidUpdates(): \Generator
    {
        yield 'blank name' => ['  ', 'A description.', 'nature', -19.7, 63.4, null, 'Activity point name must not be blank.'];
        yield 'blank description' => ['Grande Montagne', '', 'nature', -19.7, 63.4, null, 'Activity point description must not be blank.'];
        yield 'unsupported category' => ['Grande Montagne', 'A description.', 'trek', -19.7, 63.4, null, 'Activity point category "trek" is not supported.'];
        yield 'missing latitude' => ['Grande Montagne', 'A description.', 'nature', null, 63.4, null, 'Activity point latitude is required.'];
        yield 'missing longitude' => ['Grande Montagne', 'A description.', 'nature', -19.7, null, null, 'Activity point longitude is required.'];
        yield 'latitude out of bounds' => ['Grande Montagne', 'A description.', 'nature', -21.0, 63.4, null, 'Activity point latitude must be within Rodrigues bounds (-20.05 to -19.35), got -21.'];
        yield 'longitude out of bounds' => ['Grande Montagne', 'A description.', 'nature', -19.7, 64.5, null, 'Activity point longitude must be within Rodrigues bounds (62.95 to 63.95), got 64.5.'];
        yield 'blank article url' => ['Grande Montagne', 'A description.', 'nature', -19.7, 63.4, ' ', 'Activity point article URL must not be blank when provided.'];
        yield 'invalid article url' => ['Grande Montagne', 'A description.', 'nature', -19.7, 63.4, 'example.com/article', 'Activity point article URL must start with "http://", "https://" or "/", got "example.com/article".'];
    }

    #[DataProvider('invalidUpdates')]
    public function test_should_throw_when_update_is_invalid(string $name, string $description, string $category, ?float $latitude, ?float $longitude, ?string $articleUrl, string $expectedMessage): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new ActivityPoint(
            id: $id,
            name: 'Old name',
            description: 'Old description.',
            category: ActivityPointCategory::Nature,
            coordinates: new Coordinates(-19.7, 63.4),
            articleUrl: null,
        ));

        $this->expectException(InvalidActivityPointException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->useCase->handle(new UpdateActivityPointCommand(
            id: $id,
            name: $name,
            description: $description,
            category: $category,
            latitude: $latitude,
            longitude: $longitude,
            articleUrl: $articleUrl,
        ));
    }
}
