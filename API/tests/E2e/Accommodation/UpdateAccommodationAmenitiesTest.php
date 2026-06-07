<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationAmenitiesTest extends AccommodationApiTestCase
{
    public function test_should_update_accommodation_amenities(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/amenities', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['codes' => ['private_pool', 'wifi', 'parking']],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['amenities' => ['private_pool', 'wifi', 'parking']]);
    }

    public function test_should_update_accommodation_amenities_with_empty_list(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/amenities', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['codes' => []],
        ]);

        self::assertResponseStatusCodeSame(204);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/amenities', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['codes' => ['wifi']],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/amenities', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['codes' => ['wifi']],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_not_update_amenities_with_unknown_accommodation(): void
    {
        $headers = $this->authenticatedOwnerHeaders();

        self::createClient()->request('PUT', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/amenities', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['codes' => ['wifi']],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_not_update_amenities_when_empty_code_given(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/amenities', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['codes' => ['wifi', '   ']],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
