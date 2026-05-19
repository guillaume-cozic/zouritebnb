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
use App\Shared\Domain\Event\ReservationRequested;
use App\Shared\Domain\Port\UuidGenerator;
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
        $this->useCase = new RequestReservation($this->repository, $this->eventBus, $this->pricingProvider);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function testShouldCreatePendingReservationAndDispatchRequestedEvent(): void
    {
        $reservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');
        $guestUserId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1');
        UuidGenerator::freeze($reservationId);
        $this->pricingProvider->set($accommodationId, 100.0, null, $teamId);

        $id = $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: $guestUserId,
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

    public function testShouldComputeTotalPriceAndApplyWeeklyPromotion(): void
    {
        $accommodationId = Uuid::v7();
        $teamId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, 20.0, $teamId);

        $id = $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-08'),
            guestName: 'John Doe',
        ));

        $reservation = $this->repository->ofId(new ReservationId(Uuid::fromString($id)));
        self::assertNotNull($reservation);
        self::assertSame(560.0, $reservation->getPrice()->totalPrice);
        self::assertSame(20.0, $reservation->getPrice()->appliedDiscountPercentage);
    }

    public function testShouldThrowWhenAccommodationNotFound(): void
    {
        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('Accommodation not found.');

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: Uuid::v7(),
            guestUserId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));
    }

    public function testShouldThrowWhenAccommodationHasNoTeam(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0);

        $this->expectException(InvalidReservationException::class);
        $this->expectExceptionMessage('Accommodation has no owning team.');

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));
    }

    public function testShouldRejectInvalidDateRange(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, Uuid::v7());

        $this->expectException(InvalidDateRangeException::class);

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-05'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: 'John Doe',
        ));
    }

    public function testShouldRejectEmptyGuestName(): void
    {
        $accommodationId = Uuid::v7();
        $this->pricingProvider->set($accommodationId, 100.0, null, Uuid::v7());

        $this->expectException(InvalidGuestNameException::class);

        $this->useCase->handle(new RequestReservationCommand(
            accommodationId: $accommodationId,
            guestUserId: Uuid::v7(),
            checkIn: new \DateTimeImmutable('2026-05-01'),
            checkOut: new \DateTimeImmutable('2026-05-05'),
            guestName: '   ',
        ));
    }
}
