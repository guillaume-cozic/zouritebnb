<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\CreateReservation;
use App\Reservation\Domain\Command\CreateReservationCommand;
use App\Reservation\Domain\Event\ReservationConfirmed;
use App\Reservation\Domain\Event\ReservationCreated;
use App\Reservation\Domain\Exception\InvalidDateRangeException;
use App\Reservation\Domain\Exception\InvalidGuestNameException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryReservationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateReservationTest extends TestCase
{
    private InMemoryReservationRepository $repository;
    private InMemoryEventBus $eventBus;
    private CreateReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new CreateReservation($this->repository, $this->eventBus);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function testShouldCreateConfirmedReservationAndDispatchEvent(): void
    {
        $reservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');
        UuidGenerator::freeze($reservationId);

        $id = $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: $teamId,
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));

        self::assertSame($reservationId->toRfc4122(), $id);
        $reservation = $this->repository->ofId(new \App\Reservation\Domain\Entity\ReservationId($reservationId));
        self::assertNotNull($reservation);
        self::assertSame('John Doe', $reservation->getGuestName()->toString());
        self::assertSame(\App\Reservation\Domain\Entity\ReservationStatus::Confirmed, $reservation->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(ReservationCreated::class, $events[0]);
        self::assertTrue($reservationId->equals($events[0]->reservationId));
        self::assertInstanceOf(ReservationConfirmed::class, $events[1]);
        self::assertTrue($reservationId->equals($events[1]->reservationId));
    }

    public function testShouldNotCreateReservationWithInvalidDateRange(): void
    {
        $this->expectException(InvalidDateRangeException::class);
        $this->expectExceptionMessage('Check-out date must be strictly after check-in date.');

        $this->useCase->handle(new CreateReservationCommand(
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-05'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));
    }

    public function testShouldNotCreateReservationWithEmptyGuestName(): void
    {
        $this->expectException(InvalidGuestNameException::class);
        $this->expectExceptionMessage('Guest name must not be empty.');

        $this->useCase->handle(new CreateReservationCommand(
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: '   ',
        ));
    }
}
