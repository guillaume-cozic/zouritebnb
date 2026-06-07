<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class PublishAccommodationTest extends AccommodationApiTestCase
{
    public function test_should_publish_accommodation(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/publish');

        self::assertResponseStatusCodeSame(204);
    }

    public function test_should_not_publish_unknown_accommodation(): void
    {
        self::createClient()->request('PATCH', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/publish');

        self::assertResponseStatusCodeSame(404);
    }
}
