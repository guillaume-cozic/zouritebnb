<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class RefuseReservationTest extends ReservationApiTestCase
{
    public function testShouldRefusePendingReservation(): void
    {
        $id = $this->insertReservation(status: 'pending');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'status' => 'refused',
        ]);
    }

    public function testShouldReturn422WhenAlreadyConfirmed(): void
    {
        $id = $this->insertReservation(status: 'confirmed');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testShouldReturn422WhenAlreadyCancelled(): void
    {
        $id = $this->insertReservation(status: 'cancelled');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testShouldReturn422WhenAlreadyRefused(): void
    {
        $id = $this->insertReservation(status: 'refused');

        self::createClient()->request('PATCH', '/api/reservations/'.$id.'/refuse', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testShouldReturn404WhenNotFound(): void
    {
        $missing = Uuid::v7()->toRfc4122();

        self::createClient()->request('PATCH', '/api/reservations/'.$missing.'/refuse', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => new \ArrayObject(),
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
