<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class RefuseReservationTest extends ReservationApiTestCase
{
    public function test_should_refuse_pending_reservation_as_host(): void
    {
        $id = $this->insertReservation(status: 'pending');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'status' => 'refused',
        ]);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $id = $this->insertReservation(status: 'pending');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_not_host_team(): void
    {
        $id = $this->insertReservation(status: 'pending');
        $this->createAuthUser(email: 'other@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => $this->authHeaders('other@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return403_when_guest_tries_to_refuse(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $id = $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), status: 'pending', guestUserId: $guestUserId);

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return422_when_already_confirmed(): void
    {
        $id = $this->insertReservation(status: 'confirmed');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_already_cancelled(): void
    {
        $id = $this->insertReservation(status: 'cancelled');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_already_refused(): void
    {
        $id = $this->insertReservation(status: 'refused');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return404_when_not_found(): void
    {
        $missing = Uuid::v7()->toRfc4122();

        self::createClient()->request('PATCH', '/api/reservations/'.$missing.'/refuse', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
