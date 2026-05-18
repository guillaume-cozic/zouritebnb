<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\CreateReservation;
use App\Reservation\Domain\Command\CreateReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Reservation\Domain\Event\ReservationCreated;
use App\Reservation\Domain\Exception\InvalidDateRangeException;
use App\Reservation\Domain\Exception\InvalidGuestNameException;
use App\Reservation\Domain\Exception\InvalidReservationException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryAccommodationPricingProvider;
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
    private InMemoryAccommodationPricingProvider $pricingProvider;
    private CreateReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->pricingProvider = new InMemoryAccommodationPricingProvider();
        $this->useCase = new CreateReservation($this->repository, $this->eventBus, $this->pricingProvider);
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
        $this->pricingProvider->set($accommodationId, 100.0);

        $id = $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: $teamId,
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));

        self::assertSame($reservationId->toRfc4122(), $id);
        $reservation = $this->repository->ofId(new ReservationId($reservationId));
        self::assertNotNull($reservation);
        self::assertSame('John Doe', $reservation->getGuestName()->toString());
        self::assertSame(ReservationStatus::Confirmed, $reservation->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(ReservationCreated::class, $events[0]);
        self::assertTrue($reservationId->equals($events[0]->reservationId));
        self::assertInstanceOf(ReservationConfirmed::class, $events[1]);
        self::assertTrue($reservationId->equals($events[1]->reservationId));
    }

    public function testShouldComputeTotalPriceForFiveNightsWithoutPromotion(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0);

        $id = $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-06'),
            guestName: 'John Doe',
        ));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        self::assertNotNull($reservation);
        self::assertSame(500.0, $reservation->getPrice()->totalPrice);
        self::assertSame(100.0, $reservation->getPrice()->pricePerNight);
        self::assertNull($reservation->getPrice()->appliedDiscountPercentage);
    }

    public function testShouldApplyWeeklyPromotionForSevenNightsOrMore(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, 20.0);

        $id = $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-08'),
            guestName: 'John Doe',
        ));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        self::assertNotNull($reservation);
        self::assertSame(560.0, $reservation->getPrice()->totalPrice);
        self::assertSame(100.0, $reservation->getPrice()->pricePerNight);
        self::assertSame(20.0, $reservation->getPrice()->appliedDiscountPercentage);
    }

    public function testShouldNotApplyWeeklyPromotionForSixNights(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, 20.0);

        $id = $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-07'),
            guestName: 'John Doe',
        ));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        self::assertNotNull($reservation);
        self::assertSame(600.0, $reservation->getPrice()->totalPrice);
        self::assertNull($reservation->getPrice()->appliedDiscountPercentage);
    }

    public function testShouldThrowWhenAccommodationNotFound(): void
    {
        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('Accommodation not found.');

        $this->useCase->handle(new CreateReservationCommand(
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));
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
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0);

        $this->expectException(InvalidGuestNameException::class);
        $this->expectExceptionMessage('Guest name must not be empty.');

        $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: '   ',
        ));
    }
}
