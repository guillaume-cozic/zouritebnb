<?php

declare(strict_types=1);

namespace App\Tests\Unit\Accommodation\Application\UseCase;

use App\Accommodation\Application\UseCase\UpdateAccommodationCheckInOut;
use App\Accommodation\Domain\Command\UpdateAccommodationCheckInOutCommand;
use App\Accommodation\Domain\Entity\Accommodation;
use App\Accommodation\Domain\Event\AccommodationCheckInOutUpdated;
use App\Accommodation\Domain\Exception\AccommodationNotFoundException;
use App\Accommodation\Domain\Exception\InvalidCheckInOutException;
use App\Tests\Unit\Accommodation\Infrastructure\InMemoryAccommodationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateAccommodationCheckInOutTest extends TestCase
{
    private InMemoryAccommodationRepository $repository;
    private InMemoryEventBus $eventBus;
    private UpdateAccommodationCheckInOut $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryAccommodationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new UpdateAccommodationCheckInOut($this->repository, $this->eventBus);
    }

    public function test_should_update_check_in_out(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationCheckInOutCommand(
            id: $id,
            checkIn: '15:00',
            checkOut: '11:00',
        ));

        $accommodation = $this->repository->findById($id);
        $checkInOut = $accommodation->getCheckInOut();
        self::assertNotNull($checkInOut);
        self::assertSame('15:00', $checkInOut->checkIn());
        self::assertSame('11:00', $checkInOut->checkOut());
    }

    public function test_should_fall_back_to_default_times_when_null_given(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationCheckInOutCommand(
            id: $id,
            checkIn: null,
            checkOut: null,
        ));

        $accommodation = $this->repository->findById($id);
        $checkInOut = $accommodation->getCheckInOut();
        self::assertNotNull($checkInOut);
        self::assertSame('16:00', $checkInOut->checkIn());
        self::assertSame('12:00', $checkInOut->checkOut());
    }

    public function test_should_dispatch_check_in_out_updated_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->useCase->handle(new UpdateAccommodationCheckInOutCommand(
            id: $id,
            checkIn: '15:00',
            checkOut: '11:00',
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(AccommodationCheckInOutUpdated::class, $events[0]);
        self::assertTrue($id->equals($events[0]->accommodationId));
    }

    public function test_should_not_update_check_in_out_with_unknown_accommodation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(AccommodationNotFoundException::class);
        $this->expectExceptionMessage('Accommodation "01961e2f-dead-7000-beef-000000000099" not found.');

        $this->useCase->handle(new UpdateAccommodationCheckInOutCommand(
            id: $id,
            checkIn: '15:00',
            checkOut: '11:00',
        ));
    }

    public function test_should_not_update_check_in_out_with_invalid_format(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->repository->save(new Accommodation($id, 'Chalet', 'Description', 150.0));

        $this->expectException(InvalidCheckInOutException::class);

        $this->useCase->handle(new UpdateAccommodationCheckInOutCommand(
            id: $id,
            checkIn: '3pm',
            checkOut: '11:00',
        ));
    }
}
