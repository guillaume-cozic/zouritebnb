<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationGeolocationTest extends AccommodationApiTestCase
{
    public function test_should_update_accommodation_geolocation(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/geolocation', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);
    }

    public function test_should_not_update_geolocation_with_unknown_accommodation(): void
    {
        self::createClient()->request('PUT', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/geolocation', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
