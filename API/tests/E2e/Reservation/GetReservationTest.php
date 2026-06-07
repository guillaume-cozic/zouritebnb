<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class GetReservationTest extends ReservationApiTestCase
{
    public function test_should_get_reservation_as_host(): void
    {
        $id = $this->insertReservation(guestName: 'Alice Martin');

        self::createClient()->request('GET', '/api/reservations/'.$id, [
            'headers' => $this->hostAuthHeaders(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'guestName' => 'Alice Martin',
            'status' => 'pending',
        ]);
    }

    public function test_should_get_reservation_as_guest(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $id = $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestUserId: $guestUserId);

        self::createClient()->request('GET', '/api/reservations/'.$id, [
            'headers' => $this->authHeaders('guest@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['id' => $id]);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $id = $this->insertReservation();

        self::createClient()->request('GET', '/api/reservations/'.$id);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_neither_host_nor_guest(): void
    {
        // Reservation belongs to another team and another guest.
        $id = $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestUserId: Uuid::v7()->toRfc4122());

        // Authenticated user is on yet another team and is not the guest.
        $this->createAuthUser(email: 'stranger@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('GET', '/api/reservations/'.$id, [
            'headers' => $this->authHeaders('stranger@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return404_when_not_found(): void
    {
        self::createClient()->request('GET', '/api/reservations/01961e2f-dead-7000-beef-000000000099', [
            'headers' => $this->hostAuthHeaders(),
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_return404_when_id_is_not_a_valid_uuid(): void
    {
        self::createClient()->request('GET', '/api/reservations/not-a-uuid', [
            'headers' => $this->hostAuthHeaders(),
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
