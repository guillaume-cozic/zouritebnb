<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationAmenities;
use App\Accommodation\Domain\Command\UpdateAccommodationAmenitiesCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationAmenitiesUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidAmenitiesException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationAmenitiesTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationAmenities $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationAmenities($this->repository, $this->eventBus);
    }

    public function test_should_update_amenities(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationAmenitiesCommand(
            id: $id,
            codes: ['wifi', 'parking'],
        ));

        $accommodation = $this->repository->findById($id);
        self::assertNotNull($accommodation->getAmenities());
        self::assertSame(['wifi', 'parking'], $accommodation->getAmenities()->codes());
    }

    public function test_should_dispatch_amenities_updated_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationAmenitiesCommand(
            id: $id,
            codes: ['wifi'],
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationAmenitiesUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function test_should_not_update_amenities_with_unknown_accommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);
        $this->expectExceptionMessage('Accommodation "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new UpdateAccommodationAmenitiesCommand(
            id: $id,
            codes: ['wifi'],
        ));
    }

    public function test_should_not_update_amenities_with_empty_code(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidAmenitiesException::class);

        $this->useCase->handle(new UpdateAccommodationAmenitiesCommand(
            id: $id,
            codes: ['  '],
        ));
    }

    public function test_should_not_dispatch_event_when_amenities_invalid(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        try {
            $this->useCase->handle(new UpdateAccommodationAmenitiesCommand(
                id: $id,
                codes: [''],
            ));
            self::fail('Expected InvalidAmenitiesException.');
        } catch (InvalidAmenitiesException) {
            self::assertCount(0, $this->eventBus->getDispatchedEvents());
        }
    }
}
