<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class GetReservationCollectionTest extends ReservationApiTestCase
{
    public function test_should_list_team_reservations(): void
    {
        $headers = $this->hostAuthHeaders();
        $this->insertReservation(guestName: 'Alice');
        $this->insertReservation(guestName: 'Bob');
        // Other team, no guest link to the current user.
        $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestName: 'Charlie');

        $response = self::createClient()->request('GET', '/api/reservations', ['headers' => $headers]);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_also_list_reservations_where_user_is_guest(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        // One reservation where the user is the guest (hosted by another team).
        $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestName: 'Mine', guestUserId: $guestUserId);
        // One reservation that does not concern the user at all.
        $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestName: 'NotMine');

        $response = self::createClient()->request('GET', '/api/reservations', [
            'headers' => $this->authHeaders('guest@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Mine', $members[0]['guestName']);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $this->insertReservation(guestName: 'Alice');

        self::createClient()->request('GET', '/api/reservations');

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_filter_by_accommodation_id(): void
    {
        $headers = $this->hostAuthHeaders();
        $accommodationId = Uuid::v7()->toRfc4122();
        $this->insertReservation(accommodationId: $accommodationId, guestName: 'Alice');
        $this->insertReservation(accommodationId: $accommodationId, guestName: 'Bob');
        $this->insertReservation(guestName: 'Charlie');

        $response = self::createClient()->request('GET', '/api/reservations?accommodationId='.$accommodationId, ['headers' => $headers]);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_filter_by_date_range_overlap(): void
    {
        $headers = $this->hostAuthHeaders();
        $this->insertReservation(checkIn: '2026-05-01T15:00:00+00:00', checkOut: '2026-05-05T11:00:00+00:00', guestName: 'May-early');
        $this->insertReservation(checkIn: '2026-05-10T15:00:00+00:00', checkOut: '2026-05-15T11:00:00+00:00', guestName: 'May-mid');
        $this->insertReservation(checkIn: '2026-06-01T15:00:00+00:00', checkOut: '2026-06-05T11:00:00+00:00', guestName: 'June');

        $response = self::createClient()->request('GET', '/api/reservations?from=2026-05-08T00:00:00%2B00:00&to=2026-05-20T00:00:00%2B00:00', ['headers' => $headers]);

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('May-mid', $members[0]['guestName']);
    }

    public function test_should_ignore_invalid_accommodation_id_filter(): void
    {
        $headers = $this->hostAuthHeaders();
        $this->insertReservation(guestName: 'Alice');
        $this->insertReservation(guestName: 'Bob');

        $response = self::createClient()->request('GET', '/api/reservations?accommodationId=not-a-uuid', ['headers' => $headers]);

        self::assertResponseIsSuccessful();
        // Invalid UUID is ignored: the filter falls back to listing all team reservations.
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_ignore_invalid_from_and_to_filters(): void
    {
        $headers = $this->hostAuthHeaders();
        $this->insertReservation(checkIn: '2026-05-01T15:00:00+00:00', checkOut: '2026-05-05T11:00:00+00:00', guestName: 'May-early');
        $this->insertReservation(checkIn: '2026-06-01T15:00:00+00:00', checkOut: '2026-06-05T11:00:00+00:00', guestName: 'June');

        $response = self::createClient()->request('GET', '/api/reservations?from=not-a-date&to=also-not-a-date', ['headers' => $headers]);

        self::assertResponseIsSuccessful();
        // Unparseable dates are caught and ignored: no date filtering is applied.
        self::assertCount(2, $response->toArray()['member']);
    }
}
