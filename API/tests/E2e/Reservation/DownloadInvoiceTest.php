<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class DownloadInvoiceTest extends ReservationApiTestCase
{
    public function test_should_download_pdf_invoice_as_host(): void
    {
        $id = $this->insertReservation(status: 'confirmed');

        $response = self::createClient()->request('GET', '/api/reservations/'.$id.'/invoice', [
            'headers' => $this->hostAuthHeaders(),
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_should_download_pdf_invoice_as_guest(): void
    {
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());
        $id = $this->insertReservation(teamId: Uuid::v7()->toRfc4122(), status: 'confirmed', guestUserId: $guestUserId);

        self::createClient()->request('GET', '/api/reservations/'.$id.'/invoice', [
            'headers' => $this->authHeaders('guest@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/pdf');
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $id = $this->insertReservation(status: 'confirmed');

        self::createClient()->request('GET', '/api/reservations/'.$id.'/invoice');

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_neither_host_nor_guest(): void
    {
        $id = $this->insertReservation(status: 'confirmed');
        $this->createAuthUser(email: 'other@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('GET', '/api/reservations/'.$id.'/invoice', [
            'headers' => $this->authHeaders('other@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return404_when_reservation_not_paid(): void
    {
        $id = $this->insertReservation(status: 'pending');

        self::createClient()->request('GET', '/api/reservations/'.$id.'/invoice', [
            'headers' => $this->hostAuthHeaders(),
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
