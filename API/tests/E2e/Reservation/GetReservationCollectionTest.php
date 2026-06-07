<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class GetReservationCollectionTest extends ReservationApiTestCase
{
    public function test_should_list_team_reservations(): void
    {
        $this->insertReservation(guestName: 'Alice');
        $this->insertReservation(guestName: 'Bob');
        // Other team
        $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestName: 'Charlie');

        $response = self::createClient()->request('GET', '/api/reservations');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_filter_by_accommodation_id(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $this->insertReservation(accommodationId: $accommodationId, guestName: 'Alice');
        $this->insertReservation(accommodationId: $accommodationId, guestName: 'Bob');
        $this->insertReservation(guestName: 'Charlie');

        $response = self::createClient()->request('GET', '/api/reservations?accommodationId='.$accommodationId);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_filter_by_date_range_overlap(): void
    {
        $this->insertReservation(checkIn: '2026-05-01T15:00:00+00:00', checkOut: '2026-05-05T11:00:00+00:00', guestName: 'May-early');
        $this->insertReservation(checkIn: '2026-05-10T15:00:00+00:00', checkOut: '2026-05-15T11:00:00+00:00', guestName: 'May-mid');
        $this->insertReservation(checkIn: '2026-06-01T15:00:00+00:00', checkOut: '2026-06-05T11:00:00+00:00', guestName: 'June');

        $response = self::createClient()->request('GET', '/api/reservations?from=2026-05-08T00:00:00%2B00:00&to=2026-05-20T00:00:00%2B00:00');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('May-mid', $members[0]['guestName']);
    }

    public function test_should_ignore_invalid_accommodation_id_filter(): void
    {
        $this->insertReservation(guestName: 'Alice');
        $this->insertReservation(guestName: 'Bob');

        $response = self::createClient()->request('GET', '/api/reservations?accommodationId=not-a-uuid');

        self::assertResponseIsSuccessful();
        // Invalid UUID is ignored: the filter falls back to listing all team reservations.
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_ignore_invalid_from_and_to_filters(): void
    {
        $this->insertReservation(checkIn: '2026-05-01T15:00:00+00:00', checkOut: '2026-05-05T11:00:00+00:00', guestName: 'May-early');
        $this->insertReservation(checkIn: '2026-06-01T15:00:00+00:00', checkOut: '2026-06-05T11:00:00+00:00', guestName: 'June');

        $response = self::createClient()->request('GET', '/api/reservations?from=not-a-date&to=also-not-a-date');

        self::assertResponseIsSuccessful();
        // Unparseable dates are caught and ignored: no date filtering is applied.
        self::assertCount(2, $response->toArray()['member']);
    }
}
