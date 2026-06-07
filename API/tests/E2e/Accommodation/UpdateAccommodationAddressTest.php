<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationAddressTest extends AccommodationApiTestCase
{
    public function test_should_update_accommodation_address(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/address', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'street' => '12 rue de la Paix',
                'city' => 'Paris',
                'zipCode' => '75002',
                'country' => 'France',
            ],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'street' => '12 rue de la Paix',
            'city' => 'Paris',
            'zipCode' => '75002',
            'country' => 'France',
        ]);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/address', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'street' => '12 rue de la Paix',
                'city' => 'Paris',
                'zipCode' => '75002',
                'country' => 'France',
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/address', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'street' => '12 rue de la Paix',
                'city' => 'Paris',
                'zipCode' => '75002',
                'country' => 'France',
            ],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_not_update_address_with_unknown_accommodation(): void
    {
        $headers = $this->authenticatedOwnerHeaders();

        self::createClient()->request('PUT', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/address', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'street' => '12 rue de la Paix',
                'city' => 'Paris',
                'zipCode' => '75002',
                'country' => 'France',
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_not_update_address_with_empty_street(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/address', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'street' => '',
                'city' => 'Paris',
                'zipCode' => '75002',
                'country' => 'France',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
