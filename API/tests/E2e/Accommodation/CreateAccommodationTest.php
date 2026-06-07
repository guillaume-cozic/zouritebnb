<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class CreateAccommodationTest extends AccommodationApiTestCase
{
    public function test_should_create_accommodation(): void
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

    public function test_should_not_create_accommodation_with_missing_price(): void
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

    public function test_should_not_create_accommodation_with_negative_price(): void
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
