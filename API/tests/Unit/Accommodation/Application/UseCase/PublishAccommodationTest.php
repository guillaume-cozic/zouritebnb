<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\PublishAccommodation;
use App\Accommodation\Domain\Command\PublishAccommodationCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Entity\AccommodationStatus;
use App\Accommodation\Domain\Entity\Gallery;
use App\Accommodation\Domain\Event\AccommodationPublished;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\AccommodationNotPublishableException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryGalleryRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PublishAccommodationTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryGalleryRepository $galleryRepository;
    private InMemoryEventBus $eventBus;
    private PublishAccommodation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->galleryRepository = new InMemoryGalleryRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new PublishAccommodation($this->repository, $this->galleryRepository, $this->eventBus);
    }

    public function test_should_publish_accommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));
        $this->givenGalleryWithPhotos($id, Accommodation::MIN_PHOTOS_TO_PUBLISH);

        $this->useCase->handle(new PublishAccommodationCommand(id: $id));

        $accommodation = $this->repository->findById($id);
        self::assertSame(AccommodationStatus::Published, $accommodation->getStatus());
    }

    public function test_should_dispatch_accommodation_published_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));
        $this->givenGalleryWithPhotos($id, Accommodation::MIN_PHOTOS_TO_PUBLISH);

        $this->useCase->handle(new PublishAccommodationCommand(id: $id));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationPublished::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function test_should_not_publish_with_unknown_accommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);
        $this->expectExceptionMessage('Accommodation "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new PublishAccommodationCommand(id: $id));
    }

    public function test_should_not_publish_with_fewer_than_three_photos(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));
        $this->givenGalleryWithPhotos($id, 2);

        $this->expectException(AccommodationNotPublishableException::class);
        $this->expectExceptionMessage('at least 3 photos');

        $this->useCase->handle(new PublishAccommodationCommand(id: $id));
    }

    public function test_should_not_publish_without_a_title(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, '   ', 'Description', 150.0));
        $this->givenGalleryWithPhotos($id, Accommodation::MIN_PHOTOS_TO_PUBLISH);

        $this->expectException(AccommodationNotPublishableException::class);
        $this->expectExceptionMessage('a title');

        $this->useCase->handle(new PublishAccommodationCommand(id: $id));
    }

    public function test_should_not_publish_without_a_description(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', '', 150.0));
        $this->givenGalleryWithPhotos($id, Accommodation::MIN_PHOTOS_TO_PUBLISH);

        $this->expectException(AccommodationNotPublishableException::class);
        $this->expectExceptionMessage('a description');

        $this->useCase->handle(new PublishAccommodationCommand(id: $id));
    }

    public function test_should_report_every_missing_requirement_at_once(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, '', '', 150.0));
        // No gallery at all → 0 photos.

        $this->expectException(AccommodationNotPublishableException::class);
        $this->expectExceptionMessage('a title, a description and at least 3 photos');

        $this->useCase->handle(new PublishAccommodationCommand(id: $id));
    }

    public function test_should_not_publish_a_draft_that_is_still_incomplete(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));
        $this->givenGalleryWithPhotos($id, 1);

        try {
            $this->useCase->handle(new PublishAccommodationCommand(id: $id));
            self::fail('Expected AccommodationNotPublishableException.');
        } catch (AccommodationNotPublishableException) {
            // The accommodation must stay a draft and emit no event.
        }

        self::assertSame(AccommodationStatus::Draft, $this->repository->findById($id)->getStatus());
        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }

    private function givenGalleryWithPhotos(Uuid $accommodationId, int $count): void
    {
        $photoIds = [];
        for ($i = 0; $i < $count; ++$i) {
            $photoIds[] = Uuid::v7();
        }

        $this->galleryRepository->save(new Gallery($accommodationId, $photoIds));
    }
}
