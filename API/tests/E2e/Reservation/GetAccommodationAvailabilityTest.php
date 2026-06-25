<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use Symfony\Component\Uid\Uuid;

final class GetAccommodationAvailabilityTest extends ReservationApiTestCase
{
    public function test_should_list_busy_ranges_publicly_without_authentication(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $this->insertReservation(
            accommodationId: $accommodationId,
            checkIn: '2030-05-01T15:00:00+00:00',
            checkOut: '2030-05-05T11:00:00+00:00',
            status: 'confirmed',
        );

        $response = self::createClient()->request('GET', '/api/accommodations/'.$accommodationId.'/availability');

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame($accommodationId, $data['accommodationId']);
        self::assertCount(1, $data['busyRanges']);
        self::assertSame(['checkIn' => '2030-05-01', 'checkOut' => '2030-05-05'], $data['busyRanges'][0]);
    }

    public function test_should_include_pending_and_confirmed_but_exclude_cancelled_and_refused(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $this->insertReservation(accommodationId: $accommodationId, checkIn: '2030-05-01T15:00:00+00:00', checkOut: '2030-05-05T11:00:00+00:00', status: 'pending');
        $this->insertReservation(accommodationId: $accommodationId, checkIn: '2030-06-01T15:00:00+00:00', checkOut: '2030-06-05T11:00:00+00:00', status: 'confirmed');
        $this->insertReservation(accommodationId: $accommodationId, checkIn: '2030-07-01T15:00:00+00:00', checkOut: '2030-07-05T11:00:00+00:00', status: 'cancelled');
        $this->insertReservation(accommodationId: $accommodationId, checkIn: '2030-08-01T15:00:00+00:00', checkOut: '2030-08-05T11:00:00+00:00', status: 'refused');

        $response = self::createClient()->request('GET', '/api/accommodations/'.$accommodationId.'/availability');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['busyRanges']);
    }

    public function test_should_exclude_past_reservations(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        // Stay already over: must not block availability anymore.
        $this->insertReservation(accommodationId: $accommodationId, checkIn: '2020-01-01T15:00:00+00:00', checkOut: '2020-01-05T11:00:00+00:00', status: 'confirmed');

        $response = self::createClient()->request('GET', '/api/accommodations/'.$accommodationId.'/availability');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $response->toArray()['busyRanges']);
    }

    public function test_should_not_leak_other_accommodations_reservations(): void
    {
        $accommodationId = Uuid::v7()->toRfc4122();
        $this->insertReservation(accommodationId: $accommodationId, checkIn: '2030-05-01T15:00:00+00:00', checkOut: '2030-05-05T11:00:00+00:00', status: 'confirmed');
        $this->insertReservation(accommodationId: Uuid::v7()->toRfc4122(), checkIn: '2030-05-01T15:00:00+00:00', checkOut: '2030-05-05T11:00:00+00:00', status: 'confirmed');

        $response = self::createClient()->request('GET', '/api/accommodations/'.$accommodationId.'/availability');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $response->toArray()['busyRanges']);
    }

    public function test_should_return_empty_list_for_unknown_or_invalid_accommodation(): void
    {
        $response = self::createClient()->request('GET', '/api/accommodations/not-a-uuid/availability');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['busyRanges']);
    }
}
