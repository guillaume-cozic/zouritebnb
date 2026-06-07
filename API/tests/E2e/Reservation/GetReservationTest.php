<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

final class GetReservationTest extends ReservationApiTestCase
{
    public function test_should_get_reservation(): void
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

    public function test_should_return404_when_not_found(): void
    {
        self::createClient()->request('GET', '/api/reservations/01961e2f-dead-7000-beef-000000000099');

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_return404_when_id_is_not_a_valid_uuid(): void
    {
        self::createClient()->request('GET', '/api/reservations/not-a-uuid');

        self::assertResponseStatusCodeSame(404);
    }
}
