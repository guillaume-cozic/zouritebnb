<?php

declare(strict_types=1);

namespace App\Tests\Integration\Review\Infrastructure;

use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestCount;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Port\ReservationRepository;
use App\Review\Infrastructure\Doctrine\DbalCompletedStayChecker;
use App\Tests\Integration\RepositoryTestCase;
use App\Tests\Unit\Review\Infrastructure\FixedClock;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DbalCompletedStayCheckerTest extends RepositoryTestCase
{
    private const string NOW = '2026-06-01 12:00:00';

    private ReservationRepository $reservations;
    private DbalCompletedStayChecker $checker;

    #[Before]
    public function initServices(): void
    {
        $this->reservations = self::getContainer()->get(ReservationRepository::class);
        $connection = self::getContainer()->get(Connection::class);
        $this->checker = new DbalCompletedStayChecker($connection, new FixedClock(new \DateTimeImmutable(self::NOW)));
    }

    public function test_should_find_completed_stay_for_confirmed_past_reservation(): void
    {
        $guestUserId = Uuid::v4();
        $accommodationId = Uuid::v4();
        $reservationId = $this->saveConfirmedReservation(
            $guestUserId,
            $accommodationId,
            '2026-05-20',
            '2026-05-25',
        );

        self::assertTrue($this->checker->hasCompletedStay($guestUserId, $accommodationId));

        $stay = $this->checker->findCompletedStay($guestUserId, $accommodationId);
        self::assertNotNull($stay);
        self::assertEquals($reservationId, $stay->reservationId);
        self::assertEquals($accommodationId, $stay->accommodationId);
        self::assertEquals($guestUserId, $stay->guestUserId);
    }

    public function test_should_not_find_stay_when_checkout_is_in_the_future(): void
    {
        $guestUserId = Uuid::v4();
        $accommodationId = Uuid::v4();
        $this->saveConfirmedReservation($guestUserId, $accommodationId, '2026-06-10', '2026-06-15');

        self::assertFalse($this->checker->hasCompletedStay($guestUserId, $accommodationId));
        self::assertNull($this->checker->findCompletedStay($guestUserId, $accommodationId));
    }

    public function test_should_not_find_stay_when_reservation_is_not_confirmed(): void
    {
        $guestUserId = Uuid::v4();
        $accommodationId = Uuid::v4();
        // Pending reservation: created but not confirmed.
        $reservation = Reservation::request(
            id: new ReservationId(Uuid::v4()),
            accommodationId: $accommodationId,
            teamId: Uuid::v4(),
            dateRange: new DateRange(
                checkIn: new \DateTimeImmutable('2026-05-01'),
                checkOut: new \DateTimeImmutable('2026-05-05'),
            ),
            guestName: new GuestName('Pending Guest'),
            guestCount: new GuestCount(2),
            price: new ReservationPrice(totalPrice: 400.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
            guestUserId: $guestUserId,
        );
        $this->reservations->save($reservation);

        self::assertFalse($this->checker->hasCompletedStay($guestUserId, $accommodationId));
        self::assertNull($this->checker->findCompletedStay($guestUserId, $accommodationId));
    }

    public function test_should_not_find_stay_for_other_guest_or_accommodation(): void
    {
        $guestUserId = Uuid::v4();
        $accommodationId = Uuid::v4();
        $this->saveConfirmedReservation($guestUserId, $accommodationId, '2026-05-20', '2026-05-25');

        self::assertNull($this->checker->findCompletedStay(Uuid::v4(), $accommodationId));
        self::assertNull($this->checker->findCompletedStay($guestUserId, Uuid::v4()));
    }

    private function saveConfirmedReservation(
        Uuid $guestUserId,
        Uuid $accommodationId,
        string $checkIn,
        string $checkOut,
    ): Uuid {
        $id = new ReservationId(Uuid::v4());
        $reservation = Reservation::request(
            id: $id,
            accommodationId: $accommodationId,
            teamId: Uuid::v4(),
            dateRange: new DateRange(
                checkIn: new \DateTimeImmutable($checkIn),
                checkOut: new \DateTimeImmutable($checkOut),
            ),
            guestName: new GuestName('Completed Guest'),
            guestCount: new GuestCount(2),
            price: new ReservationPrice(totalPrice: 500.0, pricePerNight: 100.0, appliedDiscountPercentage: null),
            guestUserId: $guestUserId,
        );
        $reservation->confirm();
        $this->reservations->save($reservation);

        return $id->toUuid();
    }
}
