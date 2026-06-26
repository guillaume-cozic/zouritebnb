<?php

declare(strict_types=1);

namespace App\Tests\Integration\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestCount;
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

    public function test_should_save_and_find_by_id(): void
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
            guestCount: new GuestCount(4),
            price: new ReservationPrice(totalPrice: 320.0, pricePerNight: 80.0, appliedDiscountPercentage: null),
        );

        $this->repository->save($reservation);
        $found = $this->repository->ofId($id);

        self::assertNotNull($found);
        self::assertEquals($id->toString(), $found->getId()->toString());
        self::assertEquals($accommodationId->toRfc4122(), $found->getAccommodationId()->toRfc4122());
        self::assertEquals($teamId->toRfc4122(), $found->getTeamId()->toRfc4122());
        self::assertSame('John Doe', $found->getGuestName()->toString());
        self::assertSame(4, $found->getGuestCount()->value());
        self::assertSame(ReservationStatus::Confirmed, $found->getStatus());
        self::assertSame(320.0, $found->getPrice()->totalPrice);
        self::assertSame(80.0, $found->getPrice()->pricePerNight);
        self::assertNull($found->getPrice()->appliedDiscountPercentage);
        self::assertEquals(new \DateTimeImmutable('2026-05-01'), $found->getDateRange()->checkIn());
        self::assertEquals(new \DateTimeImmutable('2026-05-05'), $found->getDateRange()->checkOut());
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->ofId(new ReservationId(Uuid::v4()));

        self::assertNull($result);
    }

    public function test_should_update_existing_reservation(): void
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
            guestCount: new GuestCount(2),
            price: new ReservationPrice(totalPrice: 200.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
        );
        $this->repository->save($reservation);

        $reservation->cancel(new \DateTimeImmutable('2020-01-01'));
        $this->repository->save($reservation);

        $found = $this->repository->ofId($id);
        self::assertNotNull($found);
        self::assertSame(ReservationStatus::Cancelled, $found->getStatus());
    }

    public function test_should_detect_overlapping_reservation(): void
    {
        $accommodationId = Uuid::v4();
        $this->saveReservation(Uuid::v4(), $accommodationId, '2026-08-01', '2026-08-10', 'Existing');

        // Overlapping range → blocked.
        self::assertTrue($this->repository->hasOverlappingReservation(
            $accommodationId,
            new DateRange(new \DateTimeImmutable('2026-08-05'), new \DateTimeImmutable('2026-08-08')),
        ));
        // Same-day turnover on the departure day → allowed.
        self::assertFalse($this->repository->hasOverlappingReservation(
            $accommodationId,
            new DateRange(new \DateTimeImmutable('2026-08-10'), new \DateTimeImmutable('2026-08-12')),
        ));
        // Disjoint range → allowed.
        self::assertFalse($this->repository->hasOverlappingReservation(
            $accommodationId,
            new DateRange(new \DateTimeImmutable('2026-09-01'), new \DateTimeImmutable('2026-09-05')),
        ));
        // Another accommodation → not affected.
        self::assertFalse($this->repository->hasOverlappingReservation(
            Uuid::v4(),
            new DateRange(new \DateTimeImmutable('2026-08-05'), new \DateTimeImmutable('2026-08-08')),
        ));
    }

    public function test_should_not_count_cancelled_reservation_as_overlap(): void
    {
        $accommodationId = Uuid::v4();
        $reservation = Reservation::create(
            id: new ReservationId(Uuid::v4()),
            accommodationId: $accommodationId,
            teamId: Uuid::v4(),
            dateRange: new DateRange(new \DateTimeImmutable('2026-08-01'), new \DateTimeImmutable('2026-08-10')),
            guestName: new GuestName('Cancelled'),
            guestCount: new GuestCount(2),
            price: new ReservationPrice(totalPrice: 100.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
        );
        $reservation->cancel(new \DateTimeImmutable('2020-01-01'));
        $this->repository->save($reservation);

        self::assertFalse($this->repository->hasOverlappingReservation(
            $accommodationId,
            new DateRange(new \DateTimeImmutable('2026-08-05'), new \DateTimeImmutable('2026-08-08')),
        ));
    }

    public function test_should_list_filters_by_team_id(): void
    {
        $teamA = Uuid::v4();
        $teamB = Uuid::v4();
        $noGuest = Uuid::v4();
        $this->saveReservation($teamA, Uuid::v4(), '2026-07-01', '2026-07-05', 'A');
        $this->saveReservation($teamB, Uuid::v4(), '2026-07-01', '2026-07-05', 'B');

        $result = $this->repository->list($teamA, $noGuest, null, null, null);

        self::assertCount(1, $result);
        self::assertSame('A', $result[0]->getGuestName()->toString());
    }

    public function test_should_list_reservations_where_user_is_guest(): void
    {
        $teamA = Uuid::v4();
        $teamB = Uuid::v4();
        $guestUser = Uuid::v4();
        // Reservation hosted by teamA.
        $this->saveReservation($teamA, Uuid::v4(), '2026-07-01', '2026-07-05', 'Host');
        // Reservation hosted by teamB but where the user is the guest.
        $this->saveReservation($teamB, Uuid::v4(), '2026-07-10', '2026-07-15', 'AsGuest', $guestUser);

        $result = $this->repository->list($teamA, $guestUser, null, null, null);

        $names = array_map(static fn ($r) => $r->getGuestName()->toString(), $result);
        sort($names);
        self::assertSame(['AsGuest', 'Host'], $names);
    }

    public function test_should_list_filters_by_accommodation_id(): void
    {
        $teamId = Uuid::v4();
        $noGuest = Uuid::v4();
        $accommodation1 = Uuid::v4();
        $accommodation2 = Uuid::v4();
        $this->saveReservation($teamId, $accommodation1, '2026-08-01', '2026-08-05', 'One');
        $this->saveReservation($teamId, $accommodation2, '2026-08-01', '2026-08-05', 'Two');

        $result = $this->repository->list($teamId, $noGuest, $accommodation1, null, null);

        self::assertCount(1, $result);
        self::assertSame('One', $result[0]->getGuestName()->toString());
    }

    public function test_should_list_filters_by_date_overlap(): void
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
        $result = $this->repository->list($teamId, Uuid::v4(), null, $from, $to);

        $names = array_map(static fn ($r) => $r->getGuestName()->toString(), $result);
        sort($names);
        self::assertSame(['Inside', 'OverlapEnd', 'OverlapStart'], $names);
    }

    public function test_should_list_with_no_filters_returns_all_team_reservations(): void
    {
        $teamId = Uuid::v4();
        $this->saveReservation($teamId, Uuid::v4(), '2026-01-01', '2026-01-05', 'R1');
        $this->saveReservation($teamId, Uuid::v4(), '2026-02-01', '2026-02-05', 'R2');
        $this->saveReservation($teamId, Uuid::v4(), '2026-03-01', '2026-03-05', 'R3');

        $result = $this->repository->list($teamId, Uuid::v4(), null, null, null);

        self::assertCount(3, $result);
    }

    private function saveReservation(
        Uuid $teamId,
        Uuid $accommodationId,
        string $checkIn,
        string $checkOut,
        string $guestName,
        ?Uuid $guestUserId = null,
    ): void {
        if (null === $guestUserId) {
            $reservation = Reservation::create(
                id: new ReservationId(Uuid::v4()),
                accommodationId: $accommodationId,
                teamId: $teamId,
                dateRange: new DateRange(
                    checkIn: new \DateTimeImmutable($checkIn),
                    checkOut: new \DateTimeImmutable($checkOut),
                ),
                guestName: new GuestName($guestName),
                guestCount: new GuestCount(2),
                price: new ReservationPrice(totalPrice: 100.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
            );
        } else {
            $reservation = Reservation::request(
                id: new ReservationId(Uuid::v4()),
                accommodationId: $accommodationId,
                teamId: $teamId,
                dateRange: new DateRange(
                    checkIn: new \DateTimeImmutable($checkIn),
                    checkOut: new \DateTimeImmutable($checkOut),
                ),
                guestName: new GuestName($guestName),
                guestCount: new GuestCount(2),
                price: new ReservationPrice(totalPrice: 100.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
                guestUserId: $guestUserId,
            );
        }
        $this->repository->save($reservation);
    }
}
