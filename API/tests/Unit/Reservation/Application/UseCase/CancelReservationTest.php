<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\CancelReservation;
use App\Reservation\Domain\Command\CancelReservationCommand;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestCount;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Exception\InvalidReservationStateException;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Shared\Domain\Event\ReservationCancelled;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryReservationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CancelReservationTest extends TestCase
{
    private InMemoryReservationRepository $repository;
    private InMemoryEventBus $eventBus;
    private FixedClock $clock;
    private CancelReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->eventBus = new InMemoryEventBus();
        // Well before the 2026-05-01 check-in used by the fixtures.
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-04-01T12:00:00+00:00'));
        $this->useCase = new CancelReservation($this->repository, $this->eventBus, $this->clock);
    }

    private function givenReservation(Uuid $id, ReservationStatus $status): void
    {
        $reservation = new Reservation(
            id: new ReservationId($id),
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            dateRange: new DateRange(new \DateTimeImmutable('2026-05-01'), new \DateTimeImmutable('2026-05-05')),
            guestName: new GuestName('John'),
            guestCount: new GuestCount(2),
            status: $status,
            price: new ReservationPrice(totalPrice: 400.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
        );

        $this->repository->save($reservation);
    }

    public function test_should_cancel_pending_reservation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenReservation($id, ReservationStatus::Pending);

        $this->useCase->handle(new CancelReservationCommand($id->toRfc4122()));

        $reservation = $this->repository->ofId(new ReservationId($id));
        self::assertSame(ReservationStatus::Cancelled, $reservation->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationCancelled::class, $events[0]);
    }

    public function test_should_cancel_confirmed_reservation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');
        $this->givenReservation($id, ReservationStatus::Confirmed);

        $this->useCase->handle(new CancelReservationCommand($id->toRfc4122()));

        $reservation = $this->repository->ofId(new ReservationId($id));
        self::assertSame(ReservationStatus::Cancelled, $reservation->getStatus());
    }

    public function test_should_not_cancel_already_cancelled_reservation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000003');
        $this->givenReservation($id, ReservationStatus::Cancelled);

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('Reservation is already cancelled.');

        $this->useCase->handle(new CancelReservationCommand($id->toRfc4122()));
    }

    public function test_should_carry_the_optional_message_on_the_cancellation_event(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $this->givenReservation($id, ReservationStatus::Confirmed);

        $this->useCase->handle(new CancelReservationCommand($id->toRfc4122(), message: 'Un imprévu, désolé.'));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertInstanceOf(ReservationCancelled::class, $events[0]);
        self::assertSame('Un imprévu, désolé.', $events[0]->message);
    }

    public function test_should_not_cancel_a_refused_reservation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000004');
        $this->givenReservation($id, ReservationStatus::Refused);

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('A refused reservation cannot be cancelled.');

        $this->useCase->handle(new CancelReservationCommand($id->toRfc4122()));
    }

    public function test_should_not_cancel_when_the_stay_has_already_started(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000005');
        $this->givenReservation($id, ReservationStatus::Confirmed);
        // Now is after the 2026-05-01 check-in: the stay is in progress.
        $this->clock->setNow(new \DateTimeImmutable('2026-05-02T12:00:00+00:00'));

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('A reservation whose stay has already started or is past cannot be cancelled.');

        $this->useCase->handle(new CancelReservationCommand($id->toRfc4122()));
    }

    public function test_should_throw_not_found_when_reservation_does_not_exist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(ReservationNotFoundException::class);

        $this->useCase->handle(new CancelReservationCommand($id->toRfc4122()));
    }
}
