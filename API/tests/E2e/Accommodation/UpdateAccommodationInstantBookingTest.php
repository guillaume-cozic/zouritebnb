<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationInstantBookingTest extends AccommodationApiTestCase
{
    public function test_should_default_to_disabled(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['instantBooking' => false]);
    }

    public function test_should_enable_instant_booking(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/instant-booking', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['instantBooking' => true],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['instantBooking' => true]);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/instant-booking', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['instantBooking' => true],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/instant-booking', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['instantBooking' => true],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_reject_missing_value(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/instant-booking', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
