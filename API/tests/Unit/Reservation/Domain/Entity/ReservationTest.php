<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Exception\InvalidReservationStateException;
use App\Shared\Domain\Event\ReservationCancelled;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Shared\Domain\Event\ReservationRefused;
use App\Shared\Domain\Event\ReservationRequested;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ReservationTest extends TestCase
{
    private const string RESERVATION_UUID = '01961e2f-dead-7000-beef-000000000001';
    private const string ACCOMMODATION_UUID = '01961e2f-dead-7000-beef-000000000002';
    private const string TEAM_UUID = '01961e2f-dead-7000-beef-000000000003';
    private const string GUEST_UUID = '01961e2f-dead-7000-beef-000000000004';

    public function test_should_create_a_confirmed_reservation(): void
    {
        $id = $this->reservationId();
        $accommodationId = Uuid::fromString(self::ACCOMMODATION_UUID);
        $teamId = Uuid::fromString(self::TEAM_UUID);
        $dateRange = $this->dateRange();
        $guestName = new GuestName('Jane Doe');
        $price = $this->price();

        $reservation = Reservation::create(
            id: $id,
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: $dateRange,
            guestName: $guestName,
            price: $price,
        );

        self::assertSame($id, $reservation->getId());
        self::assertSame($accommodationId, $reservation->getAccommodationId());
        self::assertSame($teamId, $reservation->getTeamId());
        self::assertSame($dateRange, $reservation->getDateRange());
        self::assertSame($guestName, $reservation->getGuestName());
        self::assertSame($price, $reservation->getPrice());
        self::assertSame(ReservationStatus::Confirmed, $reservation->getStatus());
        self::assertNull($reservation->getGuestUserId());

        $events = $reservation->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationConfirmed::class, $events[0]);
        self::assertTrue($id->toUuid()->equals($events[0]->reservationId));
    }

    public function test_should_request_a_pending_reservation(): void
    {
        $id = $this->reservationId();
        $guestUserId = Uuid::fromString(self::GUEST_UUID);

        $reservation = Reservation::request(
            id: $id,
            accommodationId: Uuid::fromString(self::ACCOMMODATION_UUID),
            teamId: Uuid::fromString(self::TEAM_UUID),
            dateRange: $this->dateRange(),
            guestName: new GuestName('Jane Doe'),
            price: $this->price(),
            guestUserId: $guestUserId,
            note: 'Please allow early check-in',
            paymentIntentId: 'pi_123',
        );

        self::assertSame(ReservationStatus::Pending, $reservation->getStatus());
        self::assertSame($guestUserId, $reservation->getGuestUserId());

        $events = $reservation->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationRequested::class, $events[0]);
        self::assertTrue($id->toUuid()->equals($events[0]->reservationId));
        self::assertTrue($guestUserId->equals($events[0]->guestUserId));
        self::assertSame('Please allow early check-in', $events[0]->note);
        self::assertSame('pi_123', $events[0]->paymentIntentId);
    }

    public function test_should_request_with_default_null_note_and_payment_intent(): void
    {
        $reservation = Reservation::request(
            id: $this->reservationId(),
            accommodationId: Uuid::fromString(self::ACCOMMODATION_UUID),
            teamId: Uuid::fromString(self::TEAM_UUID),
            dateRange: $this->dateRange(),
            guestName: new GuestName('Jane Doe'),
            price: $this->price(),
            guestUserId: Uuid::fromString(self::GUEST_UUID),
        );

        $events = $reservation->releaseEvents();
        self::assertInstanceOf(ReservationRequested::class, $events[0]);
        self::assertNull($events[0]->note);
        self::assertNull($events[0]->paymentIntentId);
    }

    public function test_should_confirm_a_pending_reservation(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->releaseEvents();

        $reservation->confirm();

        self::assertSame(ReservationStatus::Confirmed, $reservation->getStatus());
        $events = $reservation->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationConfirmed::class, $events[0]);
    }

    public function test_should_not_confirm_an_already_confirmed_reservation(): void
    {
        $reservation = $this->confirmedReservation();

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('Reservation is already confirmed.');

        $reservation->confirm();
    }

    public function test_should_not_confirm_a_cancelled_reservation(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->cancel();

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('A cancelled reservation cannot be confirmed.');

        $reservation->confirm();
    }

    public function test_should_not_confirm_a_refused_reservation(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->refuse();

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('A refused reservation cannot be confirmed.');

        $reservation->confirm();
    }

    public function test_should_cancel_a_confirmed_reservation(): void
    {
        $reservation = $this->confirmedReservation();
        $reservation->releaseEvents();

        $reservation->cancel();

        self::assertSame(ReservationStatus::Cancelled, $reservation->getStatus());
        $events = $reservation->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationCancelled::class, $events[0]);
        self::assertTrue($reservation->getId()->toUuid()->equals($events[0]->reservationId));
    }

    public function test_should_not_cancel_an_already_cancelled_reservation(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->cancel();

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('Reservation is already cancelled.');

        $reservation->cancel();
    }

    public function test_should_refuse_a_pending_reservation(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->releaseEvents();

        $reservation->refuse();

        self::assertSame(ReservationStatus::Refused, $reservation->getStatus());
        $events = $reservation->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationRefused::class, $events[0]);
        self::assertFalse($events[0]->isAutomatic);
    }

    public function test_should_refuse_a_pending_reservation_automatically(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->releaseEvents();

        $reservation->refuse(automatic: true);

        $events = $reservation->releaseEvents();
        self::assertInstanceOf(ReservationRefused::class, $events[0]);
        self::assertTrue($events[0]->isAutomatic);
    }

    public function test_should_not_refuse_an_already_refused_reservation(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->refuse();

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('Reservation is already refused.');

        $reservation->refuse();
    }

    public function test_should_not_refuse_a_confirmed_reservation(): void
    {
        $reservation = $this->confirmedReservation();

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('Only a pending reservation can be refused.');

        $reservation->refuse();
    }

    public function test_should_not_refuse_a_cancelled_reservation(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->cancel();

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('Only a pending reservation can be refused.');

        $reservation->refuse();
    }

    private function reservationId(): ReservationId
    {
        return new ReservationId(Uuid::fromString(self::RESERVATION_UUID));
    }

    private function dateRange(): DateRange
    {
        return new DateRange(
            new \DateTimeImmutable('2026-04-13T15:00:00+00:00'),
            new \DateTimeImmutable('2026-04-15T11:00:00+00:00'),
        );
    }

    private function price(): ReservationPrice
    {
        return new ReservationPrice(
            totalPrice: 250.0,
            pricePerNight: 125.0,
            appliedDiscountPercentage: null,
        );
    }

    private function confirmedReservation(): Reservation
    {
        return Reservation::create(
            id: $this->reservationId(),
            accommodationId: Uuid::fromString(self::ACCOMMODATION_UUID),
            teamId: Uuid::fromString(self::TEAM_UUID),
            dateRange: $this->dateRange(),
            guestName: new GuestName('Jane Doe'),
            price: $this->price(),
        );
    }

    private function pendingReservation(): Reservation
    {
        return Reservation::request(
            id: $this->reservationId(),
            accommodationId: Uuid::fromString(self::ACCOMMODATION_UUID),
            teamId: Uuid::fromString(self::TEAM_UUID),
            dateRange: $this->dateRange(),
            guestName: new GuestName('Jane Doe'),
            price: $this->price(),
            guestUserId: Uuid::fromString(self::GUEST_UUID),
        );
    }
}
