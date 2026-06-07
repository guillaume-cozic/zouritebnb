<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationDescriptionTest extends AccommodationApiTestCase
{
    public function test_should_update_accommodation_description(): void
    {
        $id = $this->insertAccommodation('Old Title', 'Old description', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/description', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => 'Mon hébergement', 'description' => 'Une description détaillée...'],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'title' => 'Mon hébergement',
            'description' => 'Une description détaillée...',
        ]);
    }

    public function test_should_not_update_description_with_unknown_accommodation(): void
    {
        self::createClient()->request('PUT', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/description', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => 'Mon hébergement', 'description' => 'Une description détaillée...'],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
