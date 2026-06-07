<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationCapacityTest extends AccommodationApiTestCase
{
    public function test_should_update_accommodation_capacity(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/capacity', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'bedrooms' => 3,
                'bathrooms' => 2,
                'maxGuests' => 6,
                'singleBeds' => 2,
                'doubleBeds' => 2,
            ],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'bedrooms' => 3,
            'bathrooms' => 2,
            'maxGuests' => 6,
            'singleBeds' => 2,
            'doubleBeds' => 2,
        ]);
    }

    public function test_should_not_update_capacity_with_unknown_accommodation(): void
    {
        self::createClient()->request('PUT', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/capacity', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'bedrooms' => 3,
                'bathrooms' => 2,
                'maxGuests' => 6,
                'singleBeds' => 2,
                'doubleBeds' => 2,
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_not_update_capacity_when_negative_value_given(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/capacity', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'bedrooms' => -1,
                'bathrooms' => 2,
                'maxGuests' => 6,
                'singleBeds' => 2,
                'doubleBeds' => 2,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
