<?php

declare(strict_types=1);

namespace App\Tests\Integration\Accommodation\Infrastructure;

use App\Accommodation\Domain\Entity\Gallery;
use App\Accommodation\Domain\Port\GalleryRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineGalleryRepositoryTest extends RepositoryTestCase
{
    private GalleryRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(GalleryRepository::class);
    }

    public function test_should_save_and_find_by_accommodation_id(): void
    {
        $accommodationId = Uuid::v4();
        $photoOne = Uuid::v4();
        $photoTwo = Uuid::v4();

        $gallery = new Gallery(
            accommodationId: $accommodationId,
            photoIds: [$photoOne, $photoTwo],
        );

        $this->repository->save($gallery);
        $found = $this->repository->findByAccommodationId($accommodationId);

        self::assertEquals($accommodationId, $found->getAccommodationId());
        self::assertCount(2, $found->photoIds());
        self::assertTrue($found->hasPhoto($photoOne));
        self::assertTrue($found->hasPhoto($photoTwo));
    }

    public function test_should_return_empty_gallery_when_not_found(): void
    {
        $accommodationId = Uuid::v4();

        $found = $this->repository->findByAccommodationId($accommodationId);

        self::assertEquals($accommodationId, $found->getAccommodationId());
        self::assertSame([], $found->photoIds());
        self::assertSame(0, $found->count());
    }

    public function test_should_save_and_find_empty_gallery(): void
    {
        $accommodationId = Uuid::v4();
        $gallery = new Gallery(accommodationId: $accommodationId);

        $this->repository->save($gallery);
        $found = $this->repository->findByAccommodationId($accommodationId);

        self::assertEquals($accommodationId, $found->getAccommodationId());
        self::assertSame([], $found->photoIds());
    }

    public function test_should_update_existing_gallery(): void
    {
        $accommodationId = Uuid::v4();
        $initialPhoto = Uuid::v4();

        $gallery = new Gallery(
            accommodationId: $accommodationId,
            photoIds: [$initialPhoto],
        );
        $this->repository->save($gallery);

        $newPhotoOne = Uuid::v4();
        $newPhotoTwo = Uuid::v4();
        $updated = new Gallery(
            accommodationId: $accommodationId,
            photoIds: [$newPhotoOne, $newPhotoTwo],
        );
        $this->repository->save($updated);

        $found = $this->repository->findByAccommodationId($accommodationId);

        self::assertCount(2, $found->photoIds());
        self::assertFalse($found->hasPhoto($initialPhoto));
        self::assertTrue($found->hasPhoto($newPhotoOne));
        self::assertTrue($found->hasPhoto($newPhotoTwo));
    }

    public function test_should_preserve_photo_order(): void
    {
        $accommodationId = Uuid::v4();
        $photoOne = Uuid::v4();
        $photoTwo = Uuid::v4();
        $photoThree = Uuid::v4();

        $gallery = new Gallery(
            accommodationId: $accommodationId,
            photoIds: [$photoOne, $photoTwo, $photoThree],
        );

        $this->repository->save($gallery);
        $found = $this->repository->findByAccommodationId($accommodationId);

        $foundIds = array_map(
            static fn (Uuid $id) => $id->toRfc4122(),
            $found->photoIds(),
        );

        self::assertSame(
            [$photoOne->toRfc4122(), $photoTwo->toRfc4122(), $photoThree->toRfc4122()],
            $foundIds,
        );
    }
}
