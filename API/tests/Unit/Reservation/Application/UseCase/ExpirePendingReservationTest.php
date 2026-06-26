<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\ExpirePendingReservation;
use App\Reservation\Domain\Command\ExpirePendingReservationCommand;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestCount;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Shared\Domain\Event\ReservationRefused;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryReservationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ExpirePendingReservationTest extends TestCase
{
    private InMemoryReservationRepository $repository;
    private FixedClock $clock;
    private InMemoryEventBus $eventBus;
    private ExpirePendingReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-05-15T12:00:00+00:00'));
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new ExpirePendingReservation($this->repository, $this->clock, $this->eventBus);
    }

    private function givenReservation(Uuid $id, ReservationStatus $status): void
    {
        $this->repository->save(new Reservation(
            id: new ReservationId($id),
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            dateRange: new DateRange(new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-06-05')),
            guestName: new GuestName('John'),
            guestCount: new GuestCount(2),
            status: $status,
            price: new ReservationPrice(totalPrice: 400.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
            guestUserId: Uuid::v7(),
        ));
    }

    public function test_should_refuse_pending_reservation_when_timeout_elapsed(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenReservation($id, ReservationStatus::Pending);

        $dispatchedAt = $this->clock->now()->modify('-25 hours');

        $this->useCase->handle(new ExpirePendingReservationCommand(
            reservationId: $id->toRfc4122(),
            dispatchedAt: $dispatchedAt,
        ));

        $reservation = $this->repository->ofId(new ReservationId($id));
        self::assertSame(ReservationStatus::Refused, $reservation->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationRefused::class, $events[0]);
        self::assertTrue($events[0]->isAutomatic);
    }

    public function test_should_do_nothing_when_timeout_not_yet_elapsed(): void
    {
        $id = Uuid::v7();
        $this->givenReservation($id, ReservationStatus::Pending);

        // Dispatched 10 minutes ago, far below 24h.
        $dispatchedAt = $this->clock->now()->modify('-10 minutes');

        $this->useCase->handle(new ExpirePendingReservationCommand(
            reservationId: $id->toRfc4122(),
            dispatchedAt: $dispatchedAt,
        ));

        $reservation = $this->repository->ofId(new ReservationId($id));
        self::assertSame(ReservationStatus::Pending, $reservation->getStatus());
        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }

    public function test_should_do_nothing_when_reservation_is_already_confirmed(): void
    {
        $id = Uuid::v7();
        $this->givenReservation($id, ReservationStatus::Confirmed);

        $this->useCase->handle(new ExpirePendingReservationCommand(
            reservationId: $id->toRfc4122(),
            dispatchedAt: $this->clock->now()->modify('-25 hours'),
        ));

        $reservation = $this->repository->ofId(new ReservationId($id));
        self::assertSame(ReservationStatus::Confirmed, $reservation->getStatus());
        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }

    public function test_should_do_nothing_when_reservation_is_already_refused(): void
    {
        $id = Uuid::v7();
        $this->givenReservation($id, ReservationStatus::Refused);

        $this->useCase->handle(new ExpirePendingReservationCommand(
            reservationId: $id->toRfc4122(),
            dispatchedAt: $this->clock->now()->modify('-25 hours'),
        ));

        $reservation = $this->repository->ofId(new ReservationId($id));
        self::assertSame(ReservationStatus::Refused, $reservation->getStatus());
        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }

    public function test_should_do_nothing_when_reservation_does_not_exist(): void
    {
        $this->useCase->handle(new ExpirePendingReservationCommand(
            reservationId: Uuid::v7()->toRfc4122(),
            dispatchedAt: $this->clock->now()->modify('-25 hours'),
        ));

        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }
}
