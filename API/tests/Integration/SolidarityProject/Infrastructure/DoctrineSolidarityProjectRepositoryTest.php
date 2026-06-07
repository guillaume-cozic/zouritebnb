<?php

declare(strict_types=1);

namespace App\Tests\Integration\SolidarityProject\Infrastructure;

use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Port\SolidarityProjectRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineSolidarityProjectRepositoryTest extends RepositoryTestCase
{
    private SolidarityProjectRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(SolidarityProjectRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v7();
        $createdAt = new \DateTimeImmutable('2026-04-01T10:00:00+00:00');
        $project = new SolidarityProject(
            id: $id,
            title: 'Reforestation',
            description: 'Plant trees',
            imageUrl: 'https://example.com/img.jpg',
            status: 'active',
            createdAt: $createdAt,
        );

        $this->repository->save($project);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('Reforestation', $found->getTitle());
        self::assertSame('Plant trees', $found->getDescription());
        self::assertSame('https://example.com/img.jpg', $found->getImageUrl());
        self::assertSame('active', $found->getStatus());
        self::assertEquals($createdAt, $found->getCreatedAt());
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v7();
        $this->repository->save(new SolidarityProject(
            id: $id,
            title: 'Original',
            description: 'Original description',
            imageUrl: null,
            status: 'active',
        ));

        $this->repository->save(new SolidarityProject(
            id: $id,
            title: 'Updated',
            description: 'Updated description',
            imageUrl: 'https://example.com/new.jpg',
            status: 'closed',
        ));

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('Updated', $found->getTitle());
        self::assertSame('Updated description', $found->getDescription());
        self::assertSame('https://example.com/new.jpg', $found->getImageUrl());
        self::assertSame('closed', $found->getStatus());
    }

    public function test_should_find_all_active_projects_ordered_by_created_at_desc(): void
    {
        $older = new SolidarityProject(
            id: Uuid::v7(),
            title: 'Older active',
            description: 'desc',
            imageUrl: null,
            status: 'active',
            createdAt: new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
        $newer = new SolidarityProject(
            id: Uuid::v7(),
            title: 'Newer active',
            description: 'desc',
            imageUrl: null,
            status: 'active',
            createdAt: new \DateTimeImmutable('2026-03-01T00:00:00+00:00'),
        );
        $closed = new SolidarityProject(
            id: Uuid::v7(),
            title: 'Closed',
            description: 'desc',
            imageUrl: null,
            status: 'closed',
            createdAt: new \DateTimeImmutable('2026-02-01T00:00:00+00:00'),
        );

        $this->repository->save($older);
        $this->repository->save($newer);
        $this->repository->save($closed);

        $found = $this->repository->findAllActive();

        self::assertCount(2, $found);
        self::assertSame('Newer active', $found[0]->getTitle());
        self::assertSame('Older active', $found[1]->getTitle());
    }
}
