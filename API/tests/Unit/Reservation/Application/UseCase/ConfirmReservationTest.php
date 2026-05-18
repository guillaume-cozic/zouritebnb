<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Application\UseCase;

use App\Reservation\Application\UseCase\ConfirmReservation;
use App\Reservation\Domain\Command\ConfirmReservationCommand;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Reservation\Domain\Exception\InvalidReservationStateException;
use App\Reservation\Domain\Exception\ReservationNotFoundException;
use App\Tests\Unit\Reservation\Infrastructure\InMemoryReservationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ConfirmReservationTest extends TestCase
{
    private InMemoryReservationRepository $repository;
    private InMemoryEventBus $eventBus;
    private ConfirmReservation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryReservationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new ConfirmReservation($this->repository, $this->eventBus);
    }

    private function givenReservation(Uuid $id, ReservationStatus $status): Reservation
    {
        $reservation = new Reservation(
            id: new ReservationId($id),
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            dateRange: new DateRange(new \DateTimeImmutable('2026-05-01'), new \DateTimeImmutable('2026-05-05')),
            guestName: new GuestName('John'),
            status: $status,
            price: new \App\Reservation\Domain\Entity\ReservationPrice(totalPrice: 400.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
        );

        $this->repository->save($reservation);

        return $reservation;
    }

    public function testShouldConfirmPendingReservation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $this->givenReservation($id, ReservationStatus::Pending);

        $this->useCase->handle(new ConfirmReservationCommand($id->toRfc4122()));

        $reservation = $this->repository->ofId(new ReservationId($id));
        self::assertSame(ReservationStatus::Confirmed, $reservation->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationConfirmed::class, $events[0]);
    }

    public function testShouldNotConfirmAlreadyConfirmedReservation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');
        $this->givenReservation($id, ReservationStatus::Confirmed);

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('Reservation is already confirmed.');

        $this->useCase->handle(new ConfirmReservationCommand($id->toRfc4122()));
    }

    public function testShouldNotConfirmCancelledReservation(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000003');
        $this->givenReservation($id, ReservationStatus::Cancelled);

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('A cancelled reservation cannot be confirmed.');

        $this->useCase->handle(new ConfirmReservationCommand($id->toRfc4122()));
    }

    public function testShouldThrowNotFoundWhenReservationDoesNotExist(): void
    {
        $id = Uuid::fromString('01961e2f-dead-7000-beef-000000000099');

        $this->expectException(ReservationNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('Reservation "%s" not found.', $id->toRfc4122()));

        $this->useCase->handle(new ConfirmReservationCommand($id->toRfc4122()));
    }
}
