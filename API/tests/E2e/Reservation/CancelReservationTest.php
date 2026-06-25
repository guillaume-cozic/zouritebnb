<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class CancelReservationTest extends ReservationApiTestCase
{
    /** Far enough ahead that the stay has not started and the full-refund window is open. */
    private function futureCheckIn(): string
    {
        return (new \DateTimeImmutable('+30 days'))->format(\DateTimeInterface::ATOM);
    }

    private function futureCheckOut(): string
    {
        return (new \DateTimeImmutable('+34 days'))->format(\DateTimeInterface::ATOM);
    }

    public function test_should_cancel_reservation_as_host(): void
    {
        $id = $this->insertReservation(checkIn: $this->futureCheckIn(), checkOut: $this->futureCheckOut());

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
        $id = $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestUserId: $guestUserId, checkIn: $this->futureCheckIn(), checkOut: $this->futureCheckOut());

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

    public function test_should_expose_full_refund_preview_for_a_distant_flexible_stay(): void
    {
        // Confirmed + flexible (default snapshot) + check-in 30 days away → full refund of the amount paid.
        $id = $this->insertReservation(checkIn: $this->futureCheckIn(), checkOut: $this->futureCheckOut(), status: 'confirmed');

        self::createClient()->request('GET', '/api/reservations/'.$id, [
            'headers' => $this->hostAuthHeaders(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'cancellationPolicy' => 'flexible',
            'cancellable' => true,
            'refundPercentage' => 100,
            'refundAmount' => 460,
        ]);
    }

    public function test_should_preview_zero_refund_within_24h_under_flexible_policy(): void
    {
        // Confirmed + flexible + check-in in 12h → nothing refundable, but still cancellable.
        $id = $this->insertReservation(
            checkIn: (new \DateTimeImmutable('+12 hours'))->format(\DateTimeInterface::ATOM),
            checkOut: (new \DateTimeImmutable('+4 days'))->format(\DateTimeInterface::ATOM),
            status: 'confirmed',
        );

        self::createClient()->request('GET', '/api/reservations/'.$id, [
            'headers' => $this->hostAuthHeaders(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'cancellable' => true,
            'refundPercentage' => 0,
            'refundAmount' => 0,
        ]);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $id = $this->insertReservation(checkIn: $this->futureCheckIn(), checkOut: $this->futureCheckOut());

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_neither_host_nor_guest(): void
    {
        $id = $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), guestUserId: Uuid::v7()->toRfc4122(), checkIn: $this->futureCheckIn(), checkOut: $this->futureCheckOut());
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
        $id = $this->insertReservation(checkIn: $this->futureCheckIn(), checkOut: $this->futureCheckOut(), status: 'cancelled');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_the_stay_has_already_started(): void
    {
        // Check-in is in the past: the stay is in progress or over, so it can no longer be cancelled.
        $id = $this->insertReservation(
            checkIn: (new \DateTimeImmutable('-2 days'))->format(\DateTimeInterface::ATOM),
            checkOut: (new \DateTimeImmutable('+2 days'))->format(\DateTimeInterface::ATOM),
            status: 'confirmed',
        );

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
