<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

final class GetReservationTest extends ReservationApiTestCase
{
    public function testShouldGetReservation(): void
    {
        $id = $this->insertReservation(guestName: 'Alice Martin');

        self::createClient()->request('GET', '/api/reservations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'guestName' => 'Alice Martin',
            'status' => 'pending',
        ]);
    }

    public function testShouldReturn404WhenNotFound(): void
    {
        self::createClient()->request('GET', '/api/reservations/01961e2f-dead-7000-beef-000000000099');

        self::assertResponseStatusCodeSame(404);
    }
}
