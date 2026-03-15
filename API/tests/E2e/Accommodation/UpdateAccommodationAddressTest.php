<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationAddressTest extends AccommodationApiTestCase
{
    public function testShouldUpdateAccommodationAddress(): void
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

    public function testShouldNotUpdateAddressWithUnknownAccommodation(): void
    {
        self::createClient()->request('PUT', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/address', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'street' => '12 rue de la Paix',
                'city' => 'Paris',
                'zipCode' => '75002',
                'country' => 'France',
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testShouldNotUpdateAddressWithEmptyStreet(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/address', [
            'headers' => ['Content-Type' => 'application/ld+json'],
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
