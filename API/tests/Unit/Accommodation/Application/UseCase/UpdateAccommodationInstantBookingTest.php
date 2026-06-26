<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationInstantBooking;
use App\Accommodation\Domain\Command\UpdateAccommodationInstantBookingCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationInstantBookingUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationInstantBookingTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationInstantBooking $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationInstantBooking($this->repository, $this->eventBus);
    }

    public function test_should_default_to_disabled(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        self::assertFalse($this->repository->findById($id)->isInstantBooking());
    }

    public function test_should_enable_instant_booking(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationInstantBookingCommand(accommodationId: $id, instantBooking: true));

        self::assertTrue($this->repository->findById($id)->isInstantBooking());
    }

    public function test_should_disable_instant_booking(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0, instantBooking: true));

        $this->useCase->handle(new UpdateAccommodationInstantBookingCommand(accommodationId: $id, instantBooking: false));

        self::assertFalse($this->repository->findById($id)->isInstantBooking());
    }

    public function test_should_dispatch_instant_booking_updated_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationInstantBookingCommand(accommodationId: $id, instantBooking: true));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationInstantBookingUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function test_should_not_update_with_unknown_accommodation(): void
    {
        $this->expectException(AccommodationNotFoundException::class);

        $this->useCase->handle(new UpdateAccommodationInstantBookingCommand(
            accommodationId: Uuid::fromString('01961e2f-dead-7000-beef-000000000099'),
            instantBooking: true,
        ));
    }
}
