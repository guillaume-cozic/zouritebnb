<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class ConfirmReservationTest extends ReservationApiTestCase
{
    public function test_should_confirm_pending_reservation_as_host(): void
    {
        $id = $this->insertReservation();

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/confirm', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'status' => 'confirmed',
        ]);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $id = $this->insertReservation();

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/confirm', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_not_host_team(): void
    {
        // Reservation belongs to the default host team.
        $id = $this->insertReservation();
        // Authenticated user is on a different team (and not the guest).
        $this->createAuthUser(email: 'other@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/confirm', [
            'headers' => $this->authHeaders('other@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return403_when_guest_tries_to_confirm(): void
    {
        // The guest must not be able to confirm: only the host team can.
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $id = $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestUserId: $guestUserId);

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/confirm', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return404_when_not_found(): void
    {
        self::createClient()->request('PATCH', '/api/reservations/01961e2f-dead-7000-beef-000000000099/confirm', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_return422_when_already_confirmed(): void
    {
        $id = $this->insertReservation(status: 'confirmed');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/confirm', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_cancelled(): void
    {
        $id = $this->insertReservation(status: 'cancelled');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/confirm', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
