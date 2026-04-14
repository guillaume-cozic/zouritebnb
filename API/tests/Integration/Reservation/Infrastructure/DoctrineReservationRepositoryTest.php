<?php

declare(strict_types=1);

namespace App\Tests\Integration\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineReservationRepositoryTest extends RepositoryTestCase
{
    private ReservationRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(ReservationRepository::class);
    }

    public function testShouldSaveAndFindById(): void
    {
        $id = new ReservationId(Uuid::v4());
        $accommodationId = Uuid::v4();
        $teamId = Uuid::v4();
        $reservation = Reservation::create(
            id: $id,
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: new DateRange(
                checkIn: new \DateTimeImmutable('2026-05-01'),
                checkOut: new \DateTimeImmutable('2026-05-05'),
            ),
            guestName: new GuestName('John Doe'),
            price: new ReservationPrice(totalPrice: 320.0, pricePerNight: 80.0, appliedDiscountPercentage: null),
        );

        $this->repository->save($reservation);
        $found = $this->repository->ofId($id);

        self::assertNotNull($found);
        self::assertEquals($id->toString(), $found->getId()->toString());
        self::assertEquals($accommodationId->toRfc4122(), $found->getAccommodationId()->toRfc4122());
        self::assertEquals($teamId->toRfc4122(), $found->getTeamId()->toRfc4122());
        self::assertSame('John Doe', $found->getGuestName()->toString());
        self::assertSame(ReservationStatus::Confirmed, $found->getStatus());
        self::assertSame(320.0, $found->getPrice()->totalPrice);
        self::assertSame(80.0, $found->getPrice()->pricePerNight);
        self::assertNull($found->getPrice()->appliedDiscountPercentage);
        self::assertEquals(new \DateTimeImmutable('2026-05-01'), $found->getDateRange()->checkIn());
        self::assertEquals(new \DateTimeImmutable('2026-05-05'), $found->getDateRange()->checkOut());
    }

    public function testShouldReturnNullWhenNotFound(): void
    {
        $result = $this->repository->ofId(new ReservationId(Uuid::v4()));

        self::assertNull($result);
    }

    public function testShouldUpdateExistingReservation(): void
    {
        $id = new ReservationId(Uuid::v4());
        $reservation = Reservation::create(
            id: $id,
            accommodationId: Uuid::v4(),
            teamId: Uuid::v4(),
            dateRange: new DateRange(
                checkIn: new \DateTimeImmutable('2026-06-01'),
                checkOut: new \DateTimeImmutable('2026-06-03'),
            ),
            guestName: new GuestName('Jane'),
            price: new ReservationPrice(totalPrice: 200.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
        );
        $this->repository->save($reservation);

        $reservation->cancel();
        $this->repository->save($reservation);

        $found = $this->repository->ofId($id);
        self::assertNotNull($found);
        self::assertSame(ReservationStatus::Cancelled, $found->getStatus());
    }

    public function testShouldListFiltersByTeamId(): void
    {
        $teamA = Uuid::v4();
        $teamB = Uuid::v4();
        $this->saveReservation($teamA, Uuid::v4(), '2026-07-01', '2026-07-05', 'A');
        $this->saveReservation($teamB, Uuid::v4(), '2026-07-01', '2026-07-05', 'B');

        $result = $this->repository->list($teamA, null, null, null);

        self::assertCount(1, $result);
        self::assertSame('A', $result[0]->getGuestName()->toString());
    }

    public function testShouldListFiltersByAccommodationId(): void
    {
        $teamId = Uuid::v4();
        $accommodation1 = Uuid::v4();
        $accommodation2 = Uuid::v4();
        $this->saveReservation($teamId, $accommodation1, '2026-08-01', '2026-08-05', 'One');
        $this->saveReservation($teamId, $accommodation2, '2026-08-01', '2026-08-05', 'Two');

        $result = $this->repository->list($teamId, $accommodation1, null, null);

        self::assertCount(1, $result);
        self::assertSame('One', $result[0]->getGuestName()->toString());
    }

    public function testShouldListFiltersByDateOverlap(): void
    {
        $teamId = Uuid::v4();
        $accommodationId = Uuid::v4();
        // Inside range
        $this->saveReservation($teamId, $accommodationId, '2026-09-10', '2026-09-15', 'Inside');
        // Overlapping start
        $this->saveReservation($teamId, $accommodationId, '2026-08-25', '2026-09-05', 'OverlapStart');
        // Overlapping end
        $this->saveReservation($teamId, $accommodationId, '2026-09-25', '2026-10-05', 'OverlapEnd');
        // Fully before
        $this->saveReservation($teamId, $accommodationId, '2026-07-01', '2026-07-10', 'Before');
        // Fully after
        $this->saveReservation($teamId, $accommodationId, '2026-11-01', '2026-11-10', 'After');

        $from = new \DateTimeImmutable('2026-09-01');
        $to = new \DateTimeImmutable('2026-10-01');
        $result = $this->repository->list($teamId, null, $from, $to);

        $names = array_map(static fn ($r) => $r->getGuestName()->toString(), $result);
        sort($names);
        self::assertSame(['Inside', 'OverlapEnd', 'OverlapStart'], $names);
    }

    public function testShouldListWithNoFiltersReturnsAllTeamReservations(): void
    {
        $teamId = Uuid::v4();
        $this->saveReservation($teamId, Uuid::v4(), '2026-01-01', '2026-01-05', 'R1');
        $this->saveReservation($teamId, Uuid::v4(), '2026-02-01', '2026-02-05', 'R2');
        $this->saveReservation($teamId, Uuid::v4(), '2026-03-01', '2026-03-05', 'R3');

        $result = $this->repository->list($teamId, null, null, null);

        self::assertCount(3, $result);
    }

    private function saveReservation(
        Uuid $teamId,
        Uuid $accommodationId,
        string $checkIn,
        string $checkOut,
        string $guestName,
    ): void {
        $reservation = Reservation::create(
            id: new ReservationId(Uuid::v4()),
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: new DateRange(
                checkIn: new \DateTimeImmutable($checkIn),
                checkOut: new \DateTimeImmutable($checkOut),
            ),
            guestName: new GuestName($guestName),
            price: new ReservationPrice(totalPrice: 100.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
        );
        $this->repository->save($reservation);
    }
}
