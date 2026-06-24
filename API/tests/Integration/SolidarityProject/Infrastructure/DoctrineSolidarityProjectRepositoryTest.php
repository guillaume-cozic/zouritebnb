<?php

declare(strict_types=1);

namespace App\Tests\Integration\SolidarityProject\Infrastructure;

use App\SolidarityProject\Domain\Entity\KeyFigure;
use App\SolidarityProject\Domain\Entity\ProjectTranslation;
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
            translations: [
                'fr' => new ProjectTranslation('Reforestation', 'Plant trees', [
                    new KeyFigure('10 000', 'arbres plantés'),
                    new KeyFigure('3 ans', 'de programme'),
                ]),
                'en' => new ProjectTranslation('Reforestation', 'Plant trees', [
                    new KeyFigure('10,000', 'trees planted'),
                ]),
            ],
            imageUrl: 'https://example.com/img.jpg',
            status: 'active',
            createdAt: $createdAt,
        );

        $this->repository->save($project);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertSame('Reforestation', $found->translation('fr')->getTitle());
        self::assertSame('Plant trees', $found->translation('fr')->getDescription());
        self::assertSame('https://example.com/img.jpg', $found->getImageUrl());
        self::assertSame('active', $found->getStatus());
        self::assertEquals($createdAt, $found->getCreatedAt());
        self::assertCount(2, $found->translation('fr')->getKeyFigures());
        self::assertSame('10 000', $found->translation('fr')->getKeyFigures()[0]->value());
        self::assertSame('arbres plantés', $found->translation('fr')->getKeyFigures()[0]->label());
        self::assertSame('3 ans', $found->translation('fr')->getKeyFigures()[1]->value());
        self::assertSame('de programme', $found->translation('fr')->getKeyFigures()[1]->label());
        self::assertSame('trees planted', $found->translation('en')->getKeyFigures()[0]->label());
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
            translations: ['fr' => new ProjectTranslation('Original', 'Original description')],
            imageUrl: null,
            status: 'active',
        ));

        $this->repository->save(new SolidarityProject(
            id: $id,
            translations: ['fr' => new ProjectTranslation('Updated', 'Updated description')],
            imageUrl: 'https://example.com/new.jpg',
            status: 'closed',
        ));

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('Updated', $found->translation('fr')->getTitle());
        self::assertSame('Updated description', $found->translation('fr')->getDescription());
        self::assertSame('https://example.com/new.jpg', $found->getImageUrl());
        self::assertSame('closed', $found->getStatus());
    }

    public function test_should_find_all_active_projects_ordered_by_created_at_desc(): void
    {
        $older = new SolidarityProject(
            id: Uuid::v7(),
            translations: ['fr' => new ProjectTranslation('Older active', 'desc')],
            imageUrl: null,
            status: 'active',
            createdAt: new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
        $newer = new SolidarityProject(
            id: Uuid::v7(),
            translations: ['fr' => new ProjectTranslation('Newer active', 'desc')],
            imageUrl: null,
            status: 'active',
            createdAt: new \DateTimeImmutable('2026-03-01T00:00:00+00:00'),
        );
        $closed = new SolidarityProject(
            id: Uuid::v7(),
            translations: ['fr' => new ProjectTranslation('Closed', 'desc')],
            imageUrl: null,
            status: 'closed',
            createdAt: new \DateTimeImmutable('2026-02-01T00:00:00+00:00'),
        );

        $this->repository->save($older);
        $this->repository->save($newer);
        $this->repository->save($closed);

        $found = $this->repository->findAllActive();

        self::assertCount(2, $found);
        self::assertSame('Newer active', $found[0]->translation('fr')->getTitle());
        self::assertSame('Older active', $found[1]->translation('fr')->getTitle());
    }
}
