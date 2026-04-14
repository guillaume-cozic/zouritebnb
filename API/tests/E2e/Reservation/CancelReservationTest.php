<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

final class CancelReservationTest extends ReservationApiTestCase
{
    public function testShouldCancelReservation(): void
    {
        $id = $this->insertReservation();

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'status' => 'cancelled',
        ]);
    }

    public function testShouldReturn404WhenNotFound(): void
    {
        self::createClient()->request('PATCH', '/api/reservations/01961e2f-dead-7000-beef-000000000099/cancel', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testShouldReturn422WhenAlreadyCancelled(): void
    {
        $id = $this->insertReservation(status: 'cancelled');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/cancel', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
