<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\CancelReservation;
use App\Reservation\Domain\Command\CancelReservationCommand;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Exception\InvalidReservationStateException;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Shared\Domain\Event\ReservationCancelled;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryReservationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CancelReservationTest extends TestCase
{
    private InMemoryReservationRepository $repository;
    private InMemoryEventBus $eventBus;
    private CancelReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new CancelReservation($this->repository, $this->eventBus);
    }

    private function givenReservation(Uuid $id, ReservationStatus $status): void
    {
        $reservation = new Reservation(
            id: new ReservationId($id),
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            dateRange: new DateRange(new \DateTimeImmutable('2026-05-01'), new \DateTimeImmutable('2026-05-05')),
            guestName: new GuestName('John'),
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

    public function test_should_throw_not_found_when_reservation_does_not_exist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(ReservationNotFoundException::class);

        $this->useCase->handle(new CancelReservationCommand($id->toRfc4122()));
    }
}
