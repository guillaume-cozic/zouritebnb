<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class ReorderAccommodationPhotosTest extends AccommodationApiTestCase
{
    public function test_should_reorder_accommodation_photos(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);
        $firstPhotoId = $this->insertPhoto($id, 'first.jpg', 'first.jpg');
        $secondPhotoId = $this->insertPhoto($id, 'second.jpg', 'second.jpg');

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/photos/reorder', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['photoIds' => [$secondPhotoId, $firstPhotoId]],
        ]);

        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();

        $photos = $response->toArray()['photos'];
        self::assertSame($secondPhotoId, $photos[0]['id']);
        self::assertSame($firstPhotoId, $photos[1]['id']);
    }

    public function test_should_not_reorder_with_unknown_accommodation(): void
    {
        self::createClient()->request('PUT', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/photos/reorder', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['photoIds' => []],
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
