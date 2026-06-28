<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\CreateReservation;
use App\Reservation\Domain\Command\CreateReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Exception\InvalidDateRangeException;
use App\Reservation\Domain\Exception\InvalidGuestNameException;
use App\Reservation\Domain\Exception\InvalidReservationException;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Shared\Domain\Port\UuidGenerator;
use App\Shared\Domain\Service\StayPriceCalculator;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
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
        $this->useCase = new CreateReservation($this->repository, $this->eventBus, $this->pricingProvider, new StayPriceCalculator(), new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:00+00:00')));
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_create_confirmed_reservation_and_dispatch_event(): void
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
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationConfirmed::class, $events[0]);
        self::assertTrue($reservationId->equals($events[0]->reservationId));
    }

    public function test_should_compute_total_price_for_five_nights_without_promotion(): void
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

    public function test_should_apply_weekly_promotion_for_seven_nights_or_more(): void
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

    public function test_should_not_apply_weekly_promotion_for_six_nights(): void
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

    public function test_should_throw_when_dates_overlap_an_existing_reservation(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0);

        // An existing stay from May 1 to May 10.
        $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-10'),
            guestName: 'First Guest',
        ));

        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('These dates are no longer available for this accommodation.');

        // Overlapping request (May 5 → May 8 falls inside the existing stay).
        $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-05'),
            checkOut: new \DateTimeImmutable('2026-05-08'),
            guestName: 'Second Guest',
        ));
    }

    public function test_should_allow_same_day_turnover_on_the_departure_date(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0);

        // Existing stay leaves on May 10.
        $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-10'),
            guestName: 'First Guest',
        ));

        // A new arrival on the very same departure day is allowed.
        $id = $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-10'),
            checkOut: new \DateTimeImmutable('2026-05-12'),
            guestName: 'Second Guest',
        ));

        self::assertNotNull($this->repository->ofId(new ReservationId(Uuid::fromString($id))));
    }

    public function test_should_allow_booking_dates_freed_by_a_cancelled_reservation(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0);

        $firstId = $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-10'),
            guestName: 'First Guest',
        ));

        // Cancelled stays no longer block the dates.
        $first = $this->repository->ofId(new ReservationId(Uuid::fromString($firstId)));
        self::assertNotNull($first);
        $first->cancel(new \DateTimeImmutable('2020-01-01'));
        $this->repository->save($first);

        $id = $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-05'),
            checkOut: new \DateTimeImmutable('2026-05-08'),
            guestName: 'Second Guest',
        ));

        self::assertNotNull($this->repository->ofId(new ReservationId(Uuid::fromString($id))));
    }

    public function test_should_throw_when_accommodation_not_found(): void
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

    public function test_should_throw_when_guest_count_exceeds_capacity(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, maxGuests: 2);

        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('Guest count 3 exceeds the accommodation capacity of 2.');

        $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
            guestCount: 3,
        ));
    }

    public function test_should_store_the_guest_count(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, maxGuests: 4);

        $id = $this->useCase->handle(new CreateReservationCommand(
            accommodationId: $accommodationId,
            teamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
            guestCount: 4,
        ));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        self::assertNotNull($reservation);
        self::assertSame(4, $reservation->getGuestCount()->value());
    }

    public function test_should_not_create_reservation_with_invalid_date_range(): void
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

    public function test_should_not_create_reservation_with_empty_guest_name(): void
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
