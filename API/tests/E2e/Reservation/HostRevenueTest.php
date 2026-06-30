<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

final class HostRevenueTest extends ReservationApiTestCase
{
    public function test_should_return_the_revenue_overview_scoped_to_the_host_team(): void
    {
        $authHeaders = $this->hostAuthHeaders();

        // Two confirmed reservations on the host team (400 € each): one past → available,
        // one upcoming → pending. Plus a pending reservation (ignored) and a confirmed one
        // belonging to another team (must not leak into the host's figures).
        $this->insertReservation(
            status: 'confirmed',
            checkIn: '2000-01-01T15:00:00+00:00',
            checkOut: '2000-01-05T11:00:00+00:00',
            guestName: 'Alice',
        );
        $this->insertReservation(
            status: 'confirmed',
            checkIn: '2099-01-01T15:00:00+00:00',
            checkOut: '2099-01-05T11:00:00+00:00',
            guestName: 'Bob',
        );
        $this->insertReservation(
            status: 'pending',
            checkIn: '2099-02-01T15:00:00+00:00',
            checkOut: '2099-02-05T11:00:00+00:00',
            guestName: 'Carol',
        );
        $this->insertReservation(
            teamId: '00000000-0000-4000-8000-0000000000ff',
            status: 'confirmed',
            checkIn: '2099-03-01T15:00:00+00:00',
            checkOut: '2099-03-05T11:00:00+00:00',
            guestName: 'Other team',
        );

        $response = self::createClient()->request('GET', '/api/host/revenue', [
            'headers' => $authHeaders,
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();

        self::assertSame(800.0, (float) $data['totalEarned']);
        self::assertSame(400.0, (float) $data['pendingAmount']);
        self::assertSame(400.0, (float) $data['availableAmount']);
        self::assertSame(2, $data['confirmedReservations']);
        self::assertSame(1, $data['upcomingStays']);

        // Two distinct accommodations (insertReservation generates a random one each time).
        self::assertCount(2, $data['byAccommodation']);
        self::assertSame(800.0, (float) array_sum(array_column($data['byAccommodation'], 'amount')));

        // Payout statement: the two confirmed stays with their payout status.
        self::assertCount(2, $data['payouts']);
        $statuses = array_column($data['payouts'], 'status');
        self::assertContains('pending', $statuses);
        self::assertContains('available', $statuses);
        self::assertSame(['Bob', 'Alice'], array_column($data['payouts'], 'guestName')); // ordered by check_out DESC
    }

    public function test_should_return_zeroes_for_a_host_without_confirmed_reservations(): void
    {
        $authHeaders = $this->hostAuthHeaders();

        $response = self::createClient()->request('GET', '/api/host/revenue', [
            'headers' => $authHeaders,
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();

        self::assertSame(0.0, (float) $data['totalEarned']);
        self::assertSame(0, $data['confirmedReservations']);
        self::assertSame([], $data['payouts']);
        self::assertSame([], $data['byAccommodation']);
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        self::createClient()->request('GET', '/api/host/revenue');

        self::assertResponseStatusCodeSame(401);
    }
}
