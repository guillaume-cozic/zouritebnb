<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class CreateAccommodationTest extends AccommodationApiTestCase
{
    public function testShouldCreateAccommodation(): void
    {
        self::createClient()->request('POST', '/api/accommodations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Cozy Chalet',
                'description' => 'A warm mountain chalet',
                'price' => 150.0,
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    public function testShouldNotCreateAccommodationWithMissingPrice(): void
    {
        self::createClient()->request('POST', '/api/accommodations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Cozy Chalet',
                'description' => 'A warm mountain chalet',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['detail' => 'Price is required.']);
    }

    public function testShouldNotCreateAccommodationWithNegativePrice(): void
    {
        self::createClient()->request('POST', '/api/accommodations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Cozy Chalet',
                'description' => 'A warm mountain chalet',
                'price' => -50.0,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['detail' => 'Price must be strictly positive, got -50.']);
    }
}
