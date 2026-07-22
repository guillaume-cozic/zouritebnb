<?php

declare(strict_types=1);

namespace App\Tests\Integration\ActivityPoint\Infrastructure;

use App\ActivityPoint\Domain\Entity\ActivityPoint;
use App\ActivityPoint\Domain\Entity\ActivityPointCategory;
use App\ActivityPoint\Domain\Entity\Coordinates;
use App\ActivityPoint\Domain\Port\ActivityPointRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineActivityPointRepositoryTest extends RepositoryTestCase
{
    private ActivityPointRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(ActivityPointRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v7();
        $point = new ActivityPoint(
            id: $id,
            name: 'Trou d\'Argent',
            description: 'Plage sauvage accessible à pied par le sentier côtier.',
            category: ActivityPointCategory::Beach,
            coordinates: new Coordinates(-19.72, 63.47),
            articleUrl: 'https://example.com/trou-argent',
        );

        $this->repository->save($point);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('Trou d\'Argent', $found->getName());
        self::assertSame('Plage sauvage accessible à pied par le sentier côtier.', $found->getDescription());
        self::assertSame(ActivityPointCategory::Beach, $found->getCategory());
        self::assertSame(-19.72, $found->getCoordinates()->latitude());
        self::assertSame(63.47, $found->getCoordinates()->longitude());
        self::assertSame('https://example.com/trou-argent', $found->getArticleUrl());
    }

    public function test_should_save_point_without_article_url(): void
    {
        $id = Uuid::v7();
        $point = new ActivityPoint(
            id: $id,
            name: 'Mont Limon',
            description: 'Point culminant de Rodrigues.',
            category: ActivityPointCategory::Viewpoint,
            coordinates: new Coordinates(-19.69, 63.42),
            articleUrl: null,
        );

        $this->repository->save($point);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertNull($found->getArticleUrl());
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v7();
        $this->repository->save(new ActivityPoint(
            id: $id,
            name: 'Original name',
            description: 'Original description',
            category: ActivityPointCategory::Nature,
            coordinates: new Coordinates(-19.70, 63.40),
            articleUrl: null,
        ));

        $this->repository->save(new ActivityPoint(
            id: $id,
            name: 'Updated name',
            description: 'Updated description',
            category: ActivityPointCategory::Kitesurf,
            coordinates: new Coordinates(-19.75, 63.45),
            articleUrl: 'https://example.com/updated',
        ));

        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame('Updated name', $found->getName());
        self::assertSame('Updated description', $found->getDescription());
        self::assertSame(ActivityPointCategory::Kitesurf, $found->getCategory());
        self::assertSame(-19.75, $found->getCoordinates()->latitude());
        self::assertSame(63.45, $found->getCoordinates()->longitude());
        self::assertSame('https://example.com/updated', $found->getArticleUrl());
    }

    public function test_should_remove_existing_point(): void
    {
        $id = Uuid::v7();
        $this->repository->save(new ActivityPoint(
            id: $id,
            name: 'To remove',
            description: 'Will be removed.',
            category: ActivityPointCategory::Diving,
            coordinates: new Coordinates(-19.68, 63.43),
            articleUrl: null,
        ));

        $this->repository->remove($id);

        self::assertNull($this->repository->findById($id));
    }

    public function test_should_do_nothing_when_removing_unknown_point(): void
    {
        $this->repository->remove(Uuid::v4());

        self::assertNull($this->repository->findById(Uuid::v4()));
    }
}
