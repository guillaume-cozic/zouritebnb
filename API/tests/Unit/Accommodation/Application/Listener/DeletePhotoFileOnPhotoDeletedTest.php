<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\Listener;

use App\Accommodation\Application\Listener\DeletePhotoFileOnPhotoDeleted;
use App\Accommodation\Domain\Entity\Photo;
use App\Accommodation\Domain\Event\AccommodationPhotoDeleted;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryPhotoRepository;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryPhotoStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DeletePhotoFileOnPhotoDeletedTest extends TestCase
{
    public function testShouldDeletePhotoFromRepositoryAndStorage(): void
    {
        $photoId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');

        $photoRepository = new InMemoryPhotoRepository();
        $photoRepository->save(new Photo(
            id: $photoId,
            accommodationId: $accommodationId,
            filename: 'photo.webp',
            originalName: 'original.jpg',
            mimeType: 'image/webp',
            size: 1000,
        ));

        $photoStorage = new InMemoryPhotoStorage();
        $photoStorage->store('photo.webp', 'fake-content');

        $handler = new DeletePhotoFileOnPhotoDeleted($photoRepository, $photoStorage);

        $handler(new AccommodationPhotoDeleted(
            accommodationId: $accommodationId,
            filename: 'photo.webp',
            photoId: $photoId,
        ));

        self::assertNull($photoRepository->findById($photoId));
        self::assertFalse($photoStorage->has('photo.webp'));
    }
}
