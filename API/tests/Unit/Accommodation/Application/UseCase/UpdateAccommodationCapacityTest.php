<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationCapacity;
use App\Accommodation\Domain\Command\UpdateAccommodationCapacityCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationCapacityUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidCapacityException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationCapacityTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationCapacity $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationCapacity($this->repository, $this->eventBus);
    }

    public function test_should_update_capacity(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationCapacityCommand(
            id: $id,
            bedrooms: 3,
            bathrooms: 2,
            maxGuests: 6,
            singleBeds: 1,
            doubleBeds: 2,
        ));

        $accommodation = $this->repository->findById($id);
        $capacity = $accommodation->getCapacity();
        self::assertNotNull($capacity);
        self::assertSame(3, $capacity->bedrooms());
        self::assertSame(2, $capacity->bathrooms());
        self::assertSame(6, $capacity->maxGuests());
        self::assertSame(1, $capacity->singleBeds());
        self::assertSame(2, $capacity->doubleBeds());
    }

    public function test_should_dispatch_capacity_updated_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationCapacityCommand(
            id: $id,
            bedrooms: 3,
            bathrooms: 2,
            maxGuests: 6,
            singleBeds: 1,
            doubleBeds: 2,
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationCapacityUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function test_should_not_update_capacity_with_unknown_accommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);
        $this->expectExceptionMessage('Accommodation "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new UpdateAccommodationCapacityCommand(
            id: $id,
            bedrooms: 3,
            bathrooms: 2,
            maxGuests: 6,
            singleBeds: 1,
            doubleBeds: 2,
        ));
    }

    public function test_should_not_update_capacity_with_negative_value(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidCapacityException::class);

        $this->useCase->handle(new UpdateAccommodationCapacityCommand(
            id: $id,
            bedrooms: -1,
            bathrooms: 2,
            maxGuests: 6,
            singleBeds: 1,
            doubleBeds: 2,
        ));
    }
}
