<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationCheckInOutTest extends AccommodationApiTestCase
{
    public function test_should_update_accommodation_check_in_out(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/check-in-out', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['checkIn' => '16:00', 'checkOut' => '12:00'],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['checkIn' => '16:00', 'checkOut' => '12:00']);
    }

    public function test_should_update_check_in_out_with_defaults_when_not_provided(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/check-in-out', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['checkIn' => '16:00', 'checkOut' => '12:00']);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/check-in-out', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['checkIn' => '16:00', 'checkOut' => '12:00'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/check-in-out', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['checkIn' => '16:00', 'checkOut' => '12:00'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_not_update_check_in_out_with_unknown_accommodation(): void
    {
        $headers = $this->authenticatedOwnerHeaders();

        self::createClient()->request('PUT', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/check-in-out', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['checkIn' => '16:00', 'checkOut' => '12:00'],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_not_update_check_in_out_when_invalid_format_given(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/check-in-out', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['checkIn' => '16h00', 'checkOut' => '12:00'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
