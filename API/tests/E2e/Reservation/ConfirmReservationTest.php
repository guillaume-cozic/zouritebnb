<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

final class ConfirmReservationTest extends ReservationApiTestCase
{
    public function testShouldConfirmPendingReservation(): void
    {
        $id = $this->insertReservation();

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/confirm', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'status' => 'confirmed',
        ]);
    }

    public function testShouldReturn404WhenNotFound(): void
    {
        self::createClient()->request('PATCH', '/api/reservations/01961e2f-dead-7000-beef-000000000099/confirm', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testShouldReturn422WhenAlreadyConfirmed(): void
    {
        $id = $this->insertReservation(status: 'confirmed');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/confirm', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testShouldReturn422WhenCancelled(): void
    {
        $id = $this->insertReservation(status: 'cancelled');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/confirm', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
