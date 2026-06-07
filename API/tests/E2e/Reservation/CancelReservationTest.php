<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class CancelReservationTest extends ReservationApiTestCase
{
    public function test_should_cancel_reservation_as_host(): void
    {
        $id = $this->insertReservation();

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'status' => 'cancelled',
        ]);
    }

    public function test_should_cancel_reservation_as_guest(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $id = $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestUserId: $guestUserId);

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'status' => 'cancelled',
        ]);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $id = $this->insertReservation();

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_neither_host_nor_guest(): void
    {
        $id = $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestUserId: Uuid::v7()->toRfc4122());
        $this->createAuthUser(email: 'stranger@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => $this->authHeaders('stranger@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return404_when_not_found(): void
    {
        self::createClient()->request('PATCH', '/api/reservations/01961e2f-dead-7000-beef-000000000099/cancel', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_return422_when_already_cancelled(): void
    {
        $id = $this->insertReservation(status: 'cancelled');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
