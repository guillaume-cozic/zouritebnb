<?php

declare(strict_types=1);

namespace App\Tests\Unit\ActivityPoint\Application\UseCase;

use App\ActivityPoint\Application\UseCase\CreateActivityPoint;
use App\ActivityPoint\Domain\Command\CreateActivityPointCommand;
use App\ActivityPoint\Domain\Entity\ActivityPoint;
use App\ActivityPoint\Domain\Entity\ActivityPointCategory;
use App\ActivityPoint\Domain\Exception\InvalidActivityPointException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\ActivityPoint\Infrastructure\InMemoryActivityPointRepository;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateActivityPointTest extends TestCase
{
    private InMemoryActivityPointRepository $repository;
    private CreateActivityPoint $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryActivityPointRepository();
        $this->useCase = new CreateActivityPoint($this->repository);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_create_and_persist_an_activity_point(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        UuidGenerator::freeze($id);

        $this->useCase->handle(new CreateActivityPointCommand(
            name: 'Trou d\'Argent',
            description: 'One of the most beautiful beaches of Rodrigues.',
            category: 'beach',
            latitude: -19.7245,
            longitude: 63.4842,
            articleUrl: 'https://example.com/trou-d-argent',
        ));

        $point = $this->repository->findById($id);
        self::assertInstanceOf(ActivityPoint::class, $point);
        self::assertSame('Trou d\'Argent', $point->getName());
        self::assertSame('One of the most beautiful beaches of Rodrigues.', $point->getDescription());
        self::assertSame(ActivityPointCategory::Beach, $point->getCategory());
        self::assertSame(-19.7245, $point->getCoordinates()->latitude());
        self::assertSame(63.4842, $point->getCoordinates()->longitude());
        self::assertSame('https://example.com/trou-d-argent', $point->getArticleUrl());
    }

    public function test_should_create_an_activity_point_without_article_url(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');
        UuidGenerator::freeze($id);

        $this->useCase->handle(new CreateActivityPointCommand(
            name: 'Mont Limon',
            description: 'Highest viewpoint of the island.',
            category: 'viewpoint',
            latitude: -19.6975,
            longitude: 63.4302,
            articleUrl: null,
        ));

        $point = $this->repository->findById($id);
        self::assertInstanceOf(ActivityPoint::class, $point);
        self::assertNull($point->getArticleUrl());
    }

    public function test_should_accept_a_relative_article_url(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000003');
        UuidGenerator::freeze($id);

        $this->useCase->handle(new CreateActivityPointCommand(
            name: 'Caverne Patate',
            description: 'Underground limestone caves.',
            category: 'heritage',
            latitude: -19.7527,
            longitude: 63.3706,
            articleUrl: '/blog/caverne-patate',
        ));

        $point = $this->repository->findById($id);
        self::assertInstanceOf(ActivityPoint::class, $point);
        self::assertSame('/blog/caverne-patate', $point->getArticleUrl());
    }

    /**
     * @return \Generator<string, array{CreateActivityPointCommand, string}>
     */
    public static function invalidCommands(): \Generator
    {
        yield 'blank name' => [
            new CreateActivityPointCommand(name: '  ', description: 'A description.', category: 'nature', latitude: -19.7, longitude: 63.4, articleUrl: null),
            'Activity point name must not be blank.',
        ];

        yield 'blank description' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: '', category: 'nature', latitude: -19.7, longitude: 63.4, articleUrl: null),
            'Activity point description must not be blank.',
        ];

        yield 'unsupported category' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: 'A description.', category: 'surf', latitude: -19.7, longitude: 63.4, articleUrl: null),
            'Activity point category "surf" is not supported.',
        ];

        yield 'missing latitude' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: 'A description.', category: 'nature', latitude: null, longitude: 63.4, articleUrl: null),
            'Activity point latitude is required.',
        ];

        yield 'missing longitude' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: 'A description.', category: 'nature', latitude: -19.7, longitude: null, articleUrl: null),
            'Activity point longitude is required.',
        ];

        yield 'latitude below Rodrigues bounds' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: 'A description.', category: 'nature', latitude: -20.06, longitude: 63.4, articleUrl: null),
            'Activity point latitude must be within Rodrigues bounds (-20.05 to -19.35), got -20.06.',
        ];

        yield 'latitude above Rodrigues bounds' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: 'A description.', category: 'nature', latitude: -19.34, longitude: 63.4, articleUrl: null),
            'Activity point latitude must be within Rodrigues bounds (-20.05 to -19.35), got -19.34.',
        ];

        yield 'longitude below Rodrigues bounds' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: 'A description.', category: 'nature', latitude: -19.7, longitude: 62.94, articleUrl: null),
            'Activity point longitude must be within Rodrigues bounds (62.95 to 63.95), got 62.94.',
        ];

        yield 'longitude above Rodrigues bounds' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: 'A description.', category: 'nature', latitude: -19.7, longitude: 63.96, articleUrl: null),
            'Activity point longitude must be within Rodrigues bounds (62.95 to 63.95), got 63.96.',
        ];

        yield 'blank article url' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: 'A description.', category: 'nature', latitude: -19.7, longitude: 63.4, articleUrl: '   '),
            'Activity point article URL must not be blank when provided.',
        ];

        yield 'article url without accepted prefix' => [
            new CreateActivityPointCommand(name: 'Grande Montagne', description: 'A description.', category: 'nature', latitude: -19.7, longitude: 63.4, articleUrl: 'ftp://example.com/article'),
            'Activity point article URL must start with "http://", "https://" or "/", got "ftp://example.com/article".',
        ];
    }

    #[DataProvider('invalidCommands')]
    public function test_should_throw_when_command_is_invalid(CreateActivityPointCommand $command, string $expectedMessage): void
    {
        $this->expectException(InvalidActivityPointException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->useCase->handle($command);
    }
}
