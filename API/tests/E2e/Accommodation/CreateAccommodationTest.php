<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class CreateAccommodationTest extends AccommodationApiTestCase
{
    public function test_should_create_accommodation(): void
    {
        $headers = $this->authenticatedOwnerHeaders();

        self::createClient()->request('POST', '/api/accommodations', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Cozy Chalet',
                'description' => 'A warm mountain chalet',
                'price' => 150.0,
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    public function test_should_assign_current_user_team_to_created_accommodation(): void
    {
        $headers = $this->authenticatedOwnerHeaders();

        $response = self::createClient()->request('POST', '/api/accommodations', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Cozy Chalet',
                'description' => 'A warm mountain chalet',
                'price' => 150.0,
            ],
        ]);

        $id = $response->toArray()['id'];

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['teamId' => self::OWNER_TEAM_ID]);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        self::createClient()->request('POST', '/api/accommodations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Cozy Chalet',
                'description' => 'A warm mountain chalet',
                'price' => 150.0,
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_not_create_accommodation_with_missing_price(): void
    {
        $headers = $this->authenticatedOwnerHeaders();

        self::createClient()->request('POST', '/api/accommodations', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
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
        $headers = $this->authenticatedOwnerHeaders();

        self::createClient()->request('POST', '/api/accommodations', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
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
