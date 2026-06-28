<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\RequestReservation;
use App\Reservation\Domain\Command\RequestReservationCommand;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Exception\InvalidDateRangeException;
use App\Reservation\Domain\Exception\InvalidGuestNameException;
use App\Reservation\Domain\Exception\InvalidReservationException;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Shared\Domain\Event\ReservationRequested;
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

final class RequestReservationTest extends TestCase
{
    private InMemoryReservationRepository $repository;
    private InMemoryEventBus $eventBus;
    private InMemoryAccommodationPricingProvider $pricingProvider;
    private RequestReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->pricingProvider = new InMemoryAccommodationPricingProvider();
        $this->useCase = new RequestReservation($this->repository, $this->eventBus, $this->pricingProvider, new StayPriceCalculator(), new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:00+00:00')));
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_create_pending_reservation_and_dispatch_requested_event(): void
    {
        $reservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');
        $guestUserId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1');
        $guestTeamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000d1');
        UuidGenerator::freeze($reservationId);
        $this->pricingProvider->set($accommodationId, 100.0, null, $teamId);

        $id = $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: $guestUserId,
            guestTeamId: $guestTeamId,
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));

        self::assertSame($reservationId->toRfc4122(), $id);
        $reservation = $this->repository->ofId(new ReservationId($reservationId));
        self::assertNotNull($reservation);
        self::assertSame(ReservationStatus::Pending, $reservation->getStatus());
        self::assertTrue($teamId->equals($reservation->getTeamId()));
        self::assertNotNull($reservation->getGuestUserId());
        self::assertTrue($guestUserId->equals($reservation->getGuestUserId()));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationRequested::class, $events[0]);
        self::assertTrue($reservationId->equals($events[0]->reservationId));
        self::assertTrue($guestUserId->equals($events[0]->guestUserId));
    }

    public function test_should_auto_confirm_when_instant_booking_is_enabled(): void
    {
        $reservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');
        UuidGenerator::freeze($reservationId);
        $this->pricingProvider->set($accommodationId, 100.0, null, $teamId, instantBooking: true);

        $id = $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
            paymentIntentId: 'pi_123',
        ));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        self::assertNotNull($reservation);
        self::assertSame(ReservationStatus::Confirmed, $reservation->getStatus());

        // ReservationRequested (carries the instant flag, used by payment linking and
        // notification suppression) must precede ReservationConfirmed (triggers capture).
        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(ReservationRequested::class, $events[0]);
        self::assertTrue($events[0]->instantBooking);
        self::assertSame('pi_123', $events[0]->paymentIntentId);
        self::assertInstanceOf(ReservationConfirmed::class, $events[1]);
    }

    public function test_should_compute_total_price_and_apply_weekly_promotion(): void
    {
        $accommodationId = Uuid::v7();
        $teamId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, 20.0, $teamId);

        $id = $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-08'),
            guestName: 'John Doe',
        ));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        self::assertNotNull($reservation);
        self::assertSame(560.0, $reservation->getPrice()->totalPrice);
        self::assertSame(20.0, $reservation->getPrice()->appliedDiscountPercentage);
    }

    public function test_should_throw_when_accommodation_not_found(): void
    {
        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('Accommodation not found.');

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: Uuid::v7(),
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));
    }

    public function test_should_throw_when_host_books_own_team_accommodation(): void
    {
        $accommodationId = Uuid::v7();
        $teamId = Uuid::v7();
        // The accommodation belongs to the very team the requesting user is part of.
        $this->pricingProvider->set($accommodationId, 100.0, null, $teamId);

        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('A host cannot book an accommodation owned by their own team.');

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: $teamId,
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));
    }

    public function test_should_throw_when_dates_overlap_a_pending_request(): void
    {
        $accommodationId = Uuid::v7();
        $teamId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, $teamId);

        // A pending request (not yet confirmed) already holds May 1 → May 10.
        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-10'),
            guestName: 'First Guest',
        ));

        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('These dates are no longer available for this accommodation.');

        // A second guest cannot request overlapping dates, even though the first
        // request is still pending.
        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-04'),
            checkOut: new \DateTimeImmutable('2026-05-06'),
            guestName: 'Second Guest',
        ));
    }

    public function test_should_throw_when_accommodation_has_no_team(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0);

        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('Accommodation has no owning team.');

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));
    }

    public function test_should_reject_invalid_date_range(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, Uuid::v7());

        $this->expectException(InvalidDateRangeException::class);

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-05'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));
    }

    public function test_should_throw_when_stay_shorter_than_minimum_nights(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, Uuid::v7(), minNights: 3);

        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('shorter than the minimum');

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-03'), // 2 nights < 3
            guestName: 'John Doe',
        ));
    }

    public function test_should_throw_when_stay_longer_than_maximum_nights(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, Uuid::v7(), maxNights: 5);

        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('longer than the maximum');

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-10'), // 9 nights > 5
            guestName: 'John Doe',
        ));
    }

    public function test_should_accept_stay_within_min_and_max_nights(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, Uuid::v7(), minNights: 2, maxNights: 7);

        $id = $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'), // 4 nights
            guestName: 'John Doe',
        ));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        self::assertNotNull($reservation);
    }

    public function test_should_throw_when_guest_count_exceeds_capacity(): void
    {
        $accommodationId = Uuid::v7();
        $teamId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, $teamId, maxGuests: 2);

        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('Guest count 5 exceeds the accommodation capacity of 2.');

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
            guestCount: 5,
        ));
    }

    public function test_should_store_the_guest_count(): void
    {
        $accommodationId = Uuid::v7();
        $teamId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, $teamId, maxGuests: 6);

        $id = $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
            guestCount: 4,
        ));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        self::assertNotNull($reservation);
        self::assertSame(4, $reservation->getGuestCount()->value());
    }

    public function test_should_reject_empty_guest_name(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, Uuid::v7());

        $this->expectException(InvalidGuestNameException::class);

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            guestTeamId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: '   ',
        ));
    }
}
