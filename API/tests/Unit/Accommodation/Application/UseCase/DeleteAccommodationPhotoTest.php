<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\DeleteAccommodationPhoto;
use App\Accommodation\Domain\Command\DeleteAccommodationPhotoCommand;
use App\Accommodation\Domain\Entity\Gallery;
use App\Accommodation\Domain\Event\AccommodationPhotoDeleted;
use App\Accommodation\Domain\Exception\PhotoNotFoundException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryGalleryRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DeleteAccommodationPhotoTest extends TestCase
{
    private InMemoryGalleryRepository $galleryRepository;
    private InMemoryEventBus $eventBus;
    private DeleteAccommodationPhoto $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->galleryRepository = new InMemoryGalleryRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new DeleteAccommodationPhoto(
            $this->galleryRepository,
            $this->eventBus,
        );
    }

    public function testShouldRemovePhotoFromGallery(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $photoId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');

        $gallery = new Gallery(accommodationId: $accommodationId);
        $gallery->addPhoto($photoId);
        $this->galleryRepository->save($gallery);

        $this->useCase->handle(new DeleteAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            photoId: $photoId,
        ));

        $gallery = $this->galleryRepository->findByAccommodationId($accommodationId);
        self::assertCount(0, $gallery->photoIds());
    }

    public function testShouldDispatchEventWithFilenameAndPhotoId(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $photoId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');

        $gallery = new Gallery(accommodationId: $accommodationId);
        $gallery->addPhoto($photoId);
        $this->galleryRepository->save($gallery);

        $this->useCase->handle(new DeleteAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            photoId: $photoId,
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationPhotoDeleted::class, $events[0]);
        self::assertTrue($accommodationId->equals($events[0]->accommodationId));
        self::assertSame('01961e2f-dead-7000-beef-000000000010.webp', $events[0]->filename);
        self::assertTrue($photoId->equals($events[0]->photoId));
    }

    public function testShouldNotDeleteWhenPhotoNotInGallery(): void
    {
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $photoId = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(PhotoNotFoundException::class);
        $this->expectExceptionMessage('Photo "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new DeleteAccommodationPhotoCommand(
            accommodationId: $accommodationId,
            photoId: $photoId,
        ));
    }
}
