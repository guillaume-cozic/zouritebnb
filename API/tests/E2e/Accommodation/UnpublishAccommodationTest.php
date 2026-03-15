<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UnpublishAccommodationTest extends AccommodationApiTestCase
{
    public function testShouldUnpublishAccommodation(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0, 'published');

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/unpublish');

        self::assertResponseStatusCodeSame(204);
    }

    public function testShouldNotUnpublishUnknownAccommodation(): void
    {
        self::createClient()->request('PATCH', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/unpublish');

        self::assertResponseStatusCodeSame(404);
    }
}
