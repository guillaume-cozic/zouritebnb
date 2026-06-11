<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UploadAccommodationPhoto;
use App\Accommodation\Domain\Command\UploadAccommodationPhotoCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Entity\Gallery;
use App\Accommodation\Domain\Event\AccommodationPhotoUploaded;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidPhotoException;
use App\Accommodation\Domain\Exception\TooManyPhotosException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryGalleryRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UploadAccommodationPhotoTest extends TestCase
{
    private InMemoryAccommodationRepository $accommodationRepository;
    private InMemoryGalleryRepository $galleryRepository;
    private InMemoryEventBus $eventBus;
    private UploadAccommodationPhoto $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->accommodationRepository = new InMemoryAccommodationRepository();
        $this->galleryRepository = new InMemoryGalleryRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UploadAccommodationPhoto(
            $this->accommodationRepository,
            $this->galleryRepository,
            $this->eventBus,
        );
    }

    #[After]
    public function resetUuidGenerator(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_add_photo_to_gallery(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $photoUuid = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $this->accommodationRepository->save(new Accommodation($accommodationId, 'Chalet', 'Description', 150.0));
        UuidGenerator::freeze($photoUuid);

        $this->useCase->handle(new UploadAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            content: 'fake-image-content',
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 12345,
        ));

        $gallery = $this->galleryRepository->findByAccommodationId($accommodationId);
        self::assertCount(1, $gallery->photoIds());
        self::assertTrue($photoUuid->equals($gallery->photoIds()[0]));
    }

    public function test_should_dispatch_event_with_photo_data(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $photoUuid = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $this->accommodationRepository->save(new Accommodation($accommodationId, 'Chalet', 'Description', 150.0));
        UuidGenerator::freeze($photoUuid);

        $this->useCase->handle(new UploadAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            content: 'fake-image-content',
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 12345,
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationPhotoUploaded::class, $events[0]);
        self::assertTrue($accommodationId->equals($events[0]->accommodationId));
        self::assertTrue($photoUuid->equals($events[0]->photoId));
        self::assertSame('fake-image-content', $events[0]->content);
        self::assertSame('photo.jpg', $events[0]->originalName);
        self::assertSame('image/jpeg', $events[0]->mimeType);
        self::assertSame(12345, $events[0]->size);
    }

    public function test_should_not_upload_when_accommodation_not_found(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);
        $this->expectExceptionMessage('Accommodation "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new UploadAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            content: 'fake-image-content',
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 12345,
        ));
    }

    public function test_should_not_upload_when_gallery_is_full(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->accommodationRepository->save(new Accommodation($accommodationId, 'Chalet', 'Description', 150.0));

        $gallery = new Gallery(accommodationId: $accommodationId);
        for ($i = 0; $i < 20; ++$i) {
            $gallery->addPhoto(Uuid::fromString(\sprintf('01961e2f-dead-7000-beef-0000000001%02d', $i)));
        }
        $this->galleryRepository->save($gallery);

        $this->expectException(TooManyPhotosException::class);
        $this->expectExceptionMessage('Maximum number of photos (20) reached.');

        $this->useCase->handle(new UploadAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            content: 'fake-image-content',
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 12345,
        ));
    }

    public function test_should_not_upload_with_invalid_mime_type(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->accommodationRepository->save(new Accommodation($accommodationId, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidPhotoException::class);
        $this->expectExceptionMessage('Only JPEG, PNG and WebP images are allowed, got application/pdf.');

        $this->useCase->handle(new UploadAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            content: 'fake-pdf-content',
            originalName: 'document.pdf',
            mimeType: 'application/pdf',
            size: 5000,
        ));
    }

    public function test_should_not_upload_a_photo_larger_than_the_max_size(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->accommodationRepository->save(new Accommodation($accommodationId, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidPhotoException::class);

        $this->useCase->handle(new UploadAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            content: 'huge',
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 10 * 1024 * 1024 + 1,
        ));
    }
}
