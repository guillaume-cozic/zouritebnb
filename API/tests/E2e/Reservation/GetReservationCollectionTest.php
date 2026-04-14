<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class GetReservationCollectionTest extends ReservationApiTestCase
{
    public function testShouldListTeamReservations(): void
    {
        $this->insertReservation(guestName: 'Alice');
        $this->insertReservation(guestName: 'Bob');
        // Other team
        $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestName: 'Charlie');

        $response = self::createClient()->request('GET', '/api/reservations');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function testShouldFilterByAccommodationId(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $this->insertReservation(accommodationId: $accommodationId, guestName: 'Alice');
        $this->insertReservation(accommodationId: $accommodationId, guestName: 'Bob');
        $this->insertReservation(guestName: 'Charlie');

        $response = self::createClient()->request('GET', '/api/reservations?accommodationId='.$accommodationId);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function testShouldFilterByDateRangeOverlap(): void
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
}
