<?php

declare(strict_types=1);

namespace App\Tests\Unit\Reservation\Domain\Entity;

use App\Reservation\Domain\Entity\CancellationPolicy;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestCount;
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
        $guestCount = new GuestCount(3);
        $price = $this->price();

        $reservation = Reservation::create(
            id: $id,
            accommodationId: $accommodationId,
            teamId: $teamId,
            dateRange: $dateRange,
            guestName: $guestName,
            guestCount: $guestCount,
            price: $price,
        );

        self::assertSame($id, $reservation->getId());
        self::assertSame($accommodationId, $reservation->getAccommodationId());
        self::assertSame($teamId, $reservation->getTeamId());
        self::assertSame($dateRange, $reservation->getDateRange());
        self::assertSame($guestName, $reservation->getGuestName());
        self::assertSame($guestCount, $reservation->getGuestCount());
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
            guestCount: new GuestCount(2),
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
            guestCount: new GuestCount(2),
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
        $reservation->cancel(new \DateTimeImmutable('2026-04-01T12:00:00+00:00'));

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

        $reservation->cancel(new \DateTimeImmutable('2026-04-01T12:00:00+00:00'));

        self::assertSame(ReservationStatus::Cancelled, $reservation->getStatus());
        $events = $reservation->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ReservationCancelled::class, $events[0]);
        self::assertTrue($reservation->getId()->toUuid()->equals($events[0]->reservationId));
    }

    public function test_should_not_cancel_an_already_cancelled_reservation(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->cancel(new \DateTimeImmutable('2026-04-01T12:00:00+00:00'));

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('Reservation is already cancelled.');

        $reservation->cancel(new \DateTimeImmutable('2026-04-01T12:00:00+00:00'));
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
        $reservation->cancel(new \DateTimeImmutable('2026-04-01T12:00:00+00:00'));

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('Only a pending reservation can be refused.');

        $reservation->refuse();
    }

    public function test_should_default_cancellation_policy_to_flexible(): void
    {
        self::assertSame(CancellationPolicy::Flexible, $this->confirmedReservation()->getCancellationPolicy());
    }

    public function test_should_snapshot_the_cancellation_policy(): void
    {
        self::assertSame(CancellationPolicy::Moderate, $this->confirmedReservation(CancellationPolicy::Moderate)->getCancellationPolicy());
    }

    public function test_should_be_cancellable_before_check_in(): void
    {
        $reservation = $this->confirmedReservation();

        self::assertTrue($reservation->isCancellable(new \DateTimeImmutable('2026-04-12T15:00:00+00:00')));
        self::assertFalse($reservation->isCancellable(new \DateTimeImmutable('2026-04-13T16:00:00+00:00')));
    }

    public function test_should_not_be_cancellable_once_cancelled(): void
    {
        $reservation = $this->confirmedReservation();
        $reservation->cancel(new \DateTimeImmutable('2026-04-01T12:00:00+00:00'));

        self::assertFalse($reservation->isCancellable(new \DateTimeImmutable('2026-04-02T12:00:00+00:00')));
    }

    public function test_should_not_cancel_once_the_stay_started(): void
    {
        $reservation = $this->confirmedReservation();

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('A reservation whose stay has already started or is past cannot be cancelled.');

        $reservation->cancel(new \DateTimeImmutable('2026-04-13T16:00:00+00:00'));
    }

    public function test_should_not_cancel_a_refused_reservation(): void
    {
        $reservation = $this->pendingReservation();
        $reservation->refuse();

        $this->expectException(InvalidReservationStateException::class);
        $this->expectExceptionMessage('A refused reservation cannot be cancelled.');

        $reservation->cancel(new \DateTimeImmutable('2026-04-01T12:00:00+00:00'));
    }

    /**
     * @param non-empty-string $now
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('refundScenarios')]
    public function test_should_compute_the_refund_breakdown(CancellationPolicy $policy, string $now, int $expectedPercentage, float $expectedAmount): void
    {
        $reservation = $this->confirmedReservation($policy);

        $refund = $reservation->refundBreakdown(new \DateTimeImmutable($now));

        self::assertSame($policy, $refund->policy);
        self::assertSame(250.0, $refund->totalPaid);
        self::assertSame($expectedPercentage, $refund->refundPercentage);
        self::assertSame($expectedAmount, $refund->refundAmount);
    }

    /**
     * @return \Generator<string, array{CancellationPolicy, string, int, float}>
     */
    public static function refundScenarios(): \Generator
    {
        // Check-in is 2026-04-13T15:00:00+00:00.
        yield 'flexible, 48h before → full' => [CancellationPolicy::Flexible, '2026-04-11T15:00:00+00:00', 100, 250.0];
        yield 'flexible, 12h before → nothing' => [CancellationPolicy::Flexible, '2026-04-13T03:00:00+00:00', 0, 0.0];
        yield 'moderate, 6 days before → full' => [CancellationPolicy::Moderate, '2026-04-07T15:00:00+00:00', 100, 250.0];
        yield 'moderate, 2 days before → half' => [CancellationPolicy::Moderate, '2026-04-11T15:00:00+00:00', 50, 125.0];
    }

    public function test_should_fully_refund_a_pending_request_regardless_of_timing(): void
    {
        // A pending request was never captured, so cancelling it always returns everything,
        // even within 24h of check-in under a flexible policy.
        $reservation = $this->pendingReservation();

        $refund = $reservation->refundBreakdown(new \DateTimeImmutable('2026-04-13T03:00:00+00:00'));

        self::assertSame(100, $refund->refundPercentage);
        self::assertSame(250.0, $refund->refundAmount);
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

    private function confirmedReservation(CancellationPolicy $policy = CancellationPolicy::Flexible): Reservation
    {
        return Reservation::create(
            id: $this->reservationId(),
            accommodationId: Uuid::fromString(self::ACCOMMODATION_UUID),
            teamId: Uuid::fromString(self::TEAM_UUID),
            dateRange: $this->dateRange(),
            guestName: new GuestName('Jane Doe'),
            guestCount: new GuestCount(2),
            price: $this->price(),
            cancellationPolicy: $policy,
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
            guestCount: new GuestCount(2),
            price: $this->price(),
            guestUserId: Uuid::fromString(self::GUEST_UUID),
        );
    }
}
