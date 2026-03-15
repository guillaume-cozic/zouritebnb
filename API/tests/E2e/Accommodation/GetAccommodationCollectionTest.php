<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class GetAccommodationCollectionTest extends AccommodationApiTestCase
{
    public function testShouldListAccommodations(): void
    {
        $this->insertAccommodation('Beach Villa', 'Luxury beach villa', 320.0);
        $this->insertAccommodation('Mountain Cabin', 'Cozy cabin', 110.0);

        $response = self::createClient()->request('GET', '/api/accommodations');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function testShouldReturnEmptyCollection(): void
    {
        $response = self::createClient()->request('GET', '/api/accommodations');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }
}
