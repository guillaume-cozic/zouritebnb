<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationPriceTest extends AccommodationApiTestCase
{
    public function test_should_update_accommodation_price(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/price', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['price' => 200.0],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['price' => 200]);
    }

    public function test_should_not_update_price_with_unknown_accommodation(): void
    {
        self::createClient()->request('PATCH', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/price', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['price' => 200.0],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_not_update_price_when_invalid_price_given(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/price', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['price' => -50.0],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
