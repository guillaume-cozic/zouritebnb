<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Entity\Gallery;
use App\Accommodation\Domain\Exception\TooManyPhotosException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GalleryTest extends TestCase
{
    public function test_should_add_photo(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $photoId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $gallery = new Gallery(accommodationId: $accommodationId);

        $gallery->addPhoto($photoId);

        self::assertCount(1, $gallery->photoIds());
        self::assertTrue($photoId->equals($gallery->photoIds()[0]));
    }

    public function test_should_remove_photo(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $photoId1 = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $photoId2 = Uuid::fromString('01961e2f-dead-7000-beef-000000000011');
        $gallery = new Gallery(accommodationId: $accommodationId);
        $gallery->addPhoto($photoId1);
        $gallery->addPhoto($photoId2);

        $gallery->removePhoto($photoId1);

        self::assertCount(1, $gallery->photoIds());
        self::assertTrue($photoId2->equals($gallery->photoIds()[0]));
    }

    public function test_should_reorder_photos(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $photoId1 = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $photoId2 = Uuid::fromString('01961e2f-dead-7000-beef-000000000011');
        $gallery = new Gallery(accommodationId: $accommodationId);
        $gallery->addPhoto($photoId1);
        $gallery->addPhoto($photoId2);

        $gallery->reorder([$photoId2, $photoId1]);

        self::assertTrue($photoId2->equals($gallery->photoIds()[0]));
        self::assertTrue($photoId1->equals($gallery->photoIds()[1]));
    }

    public function test_should_not_exceed_max_photos(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $gallery = new Gallery(accommodationId: $accommodationId);

        for ($i = 0; $i < 20; ++$i) {
            $gallery->addPhoto(Uuid::fromString(\sprintf('01961e2f-dead-7000-beef-0000000001%02d', $i)));
        }

        $this->expectException(TooManyPhotosException::class);
        $this->expectExceptionMessage('Maximum number of photos (20) reached.');

        $gallery->addPhoto(Uuid::fromString('01961e2f-dead-7000-beef-000000000199'));
    }

    public function test_should_count_photos(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $gallery = new Gallery(accommodationId: $accommodationId);

        self::assertSame(0, $gallery->count());

        $gallery->addPhoto(Uuid::fromString('01961e2f-dead-7000-beef-000000000010'));
        self::assertSame(1, $gallery->count());
    }
}
