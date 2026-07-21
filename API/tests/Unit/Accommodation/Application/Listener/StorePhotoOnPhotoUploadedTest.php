<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\Listener;

use App\Accommodation\Application\Listener\StorePhotoOnPhotoUploaded;
use App\Accommodation\Domain\Event\AccommodationPhotoUploaded;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryImageTransformer;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryPhotoRepository;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryPhotoStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class StorePhotoOnPhotoUploadedTest extends TestCase
{
    public function test_should_transform_store_and_persist_photo(): void
    {
        $photoRepository = new InMemoryPhotoRepository();
        $photoStorage = new InMemoryPhotoStorage();
        $imageTransformer = new InMemoryImageTransformer();

        $handler = new StorePhotoOnPhotoUploaded($imageTransformer, $photoRepository, $photoStorage);

        $photoId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');

        $handler(new AccommodationPhotoUploaded(
            accommodationId: $accommodationId,
            photoId: $photoId,
            content: 'fake-image-content',
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 12345,
        ));

        $photo = $photoRepository->findById($photoId);
        self::assertNotNull($photo);
        self::assertSame('01961e2f-dead-7000-beef-000000000010.webp', $photo->getFilename());
        self::assertSame('photo.jpg', $photo->getOriginalName());
        self::assertSame('image/webp', $photo->getMimeType());
        self::assertTrue($accommodationId->equals($photo->getAccommodationId()));
        self::assertTrue($photoStorage->has('01961e2f-dead-7000-beef-000000000010.webp'));
        self::assertTrue($photoStorage->has('01961e2f-dead-7000-beef-000000000010-thumb.webp'));
        self::assertSame('thumb:fake-image-content', $photoStorage->get('01961e2f-dead-7000-beef-000000000010-thumb.webp'));
    }
}
