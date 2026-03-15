<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class GetAccommodationCollectionTest extends AccommodationApiTestCase
{
    public function testShouldListAccommodations(): void
    {
        $this->insertAccommodation('Beach Villa', 'Luxury beach villa', 320.0, 'published');
        $this->insertAccommodation('Mountain Cabin', 'Cozy cabin', 110.0, 'published');

        $response = self::createClient()->request('GET', '/api/accommodations');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function testShouldNotListDraftAccommodations(): void
    {
        $this->insertAccommodation('Draft Villa', 'A draft', 100.0, 'draft');
        $this->insertAccommodation('Published Cabin', 'A cabin', 150.0, 'published');

        $response = self::createClient()->request('GET', '/api/accommodations');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $response->toArray()['member']);
        self::assertSame('Published Cabin', $response->toArray()['member'][0]['title']);
    }

    public function testShouldReturnEmptyCollection(): void
    {
        $response = self::createClient()->request('GET', '/api/accommodations');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }
}
