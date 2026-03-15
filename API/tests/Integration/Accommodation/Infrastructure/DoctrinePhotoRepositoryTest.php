<?php

declare(strict_types=1);

namespace App\Tests\Integration\Accommodation\Infrastructure;

use App\Accommodation\Domain\Entity\Photo;
use App\Accommodation\Domain\Port\PhotoRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrinePhotoRepositoryTest extends RepositoryTestCase
{
    private PhotoRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(PhotoRepository::class);
    }

    public function testShouldSaveAndFindById(): void
    {
        $id = Uuid::v7();
        $accommodationId = Uuid::v7();

        $photo = new Photo(
            id: $id,
            accommodationId: $accommodationId,
            filename: 'abc123.jpg',
            originalName: 'living-room.jpg',
            mimeType: 'image/jpeg',
            size: 204800,
        );

        $this->repository->save($photo);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertEquals($accommodationId, $found->getAccommodationId());
        self::assertSame('abc123.jpg', $found->getFilename());
        self::assertSame('living-room.jpg', $found->getOriginalName());
        self::assertSame('image/jpeg', $found->getMimeType());
        self::assertSame(204800, $found->getSize());
    }

    public function testShouldReturnNullWhenNotFound(): void
    {
        $result = $this->repository->findById(Uuid::v7());

        self::assertNull($result);
    }

    public function testShouldDeletePhoto(): void
    {
        $id = Uuid::v7();

        $photo = new Photo(
            id: $id,
            accommodationId: Uuid::v7(),
            filename: 'to-delete.jpg',
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 102400,
        );

        $this->repository->save($photo);
        self::assertNotNull($this->repository->findById($id));

        $this->repository->delete($photo);
        self::assertNull($this->repository->findById($id));
    }

    public function testShouldCountByAccommodationId(): void
    {
        $accommodationId = Uuid::v7();

        $photo1 = new Photo(
            id: Uuid::v7(),
            accommodationId: $accommodationId,
            filename: 'photo1.jpg',
            originalName: 'first.jpg',
            mimeType: 'image/jpeg',
            size: 100000,
        );

        $photo2 = new Photo(
            id: Uuid::v7(),
            accommodationId: $accommodationId,
            filename: 'photo2.png',
            originalName: 'second.png',
            mimeType: 'image/png',
            size: 200000,
        );

        $photo3 = new Photo(
            id: Uuid::v7(),
            accommodationId: Uuid::v7(),
            filename: 'other.jpg',
            originalName: 'other.jpg',
            mimeType: 'image/jpeg',
            size: 150000,
        );

        $this->repository->save($photo1);
        $this->repository->save($photo2);
        $this->repository->save($photo3);

        self::assertSame(2, $this->repository->countByAccommodationId($accommodationId));
    }
}
