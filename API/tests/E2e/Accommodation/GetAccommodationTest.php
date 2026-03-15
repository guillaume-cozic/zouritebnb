<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class GetAccommodationTest extends AccommodationApiTestCase
{
    public function testShouldGetAccommodation(): void
    {
        $id = $this->insertAccommodation('Beach Villa', 'Luxury beach villa', 320.0);

        static::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'title' => 'Beach Villa',
            'description' => 'Luxury beach villa',
            'price' => 320,
        ]);
    }

    public function testShouldNotGetUnknownAccommodation(): void
    {
        static::createClient()->request('GET', '/api/accommodations/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
