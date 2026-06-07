<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use App\Tests\E2e\AssertsOpenApiContract;

final class GetAccommodationTest extends AccommodationApiTestCase
{
    use AssertsOpenApiContract;

    public function test_should_get_accommodation(): void
    {
        $id = $this->insertAccommodation('Beach Villa', 'Luxury beach villa', 320.0);

        $response = static::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'title' => 'Beach Villa',
            'description' => 'Luxury beach villa',
            'price' => 320,
        ]);
        $this->assertResponseMatchesOpenApiContract($response, 'GET', '/api/accommodations/{id}');
    }

    public function test_should_not_get_unknown_accommodation(): void
    {
        static::createClient()->request('GET', '/api/accommodations/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
