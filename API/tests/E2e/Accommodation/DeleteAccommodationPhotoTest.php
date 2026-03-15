<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class DeleteAccommodationPhotoTest extends AccommodationApiTestCase
{
    public function testShouldDeletePhoto(): void
    {
        $accommodationId = $this->insertAccommodation('Chalet', 'A cozy chalet', 150.0);
        $photoId = $this->insertPhoto($accommodationId);

        self::createClient()->request('DELETE', '/api/accommodations/'.$accommodationId.'/photos/'.$photoId);

        self::assertResponseStatusCodeSame(204);
    }

    public function testShouldNotDeleteWhenPhotoNotFound(): void
    {
        $accommodationId = $this->insertAccommodation('Chalet', 'A cozy chalet', 150.0);

        self::createClient()->request('DELETE', '/api/accommodations/'.$accommodationId.'/photos/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }

    public function testShouldNotDeletePhotoFromWrongAccommodation(): void
    {
        $accommodationId1 = $this->insertAccommodation('Chalet 1', 'First chalet', 150.0);
        $accommodationId2 = $this->insertAccommodation('Chalet 2', 'Second chalet', 200.0);
        $photoId = $this->insertPhoto($accommodationId1);

        self::createClient()->request('DELETE', '/api/accommodations/'.$accommodationId2.'/photos/'.$photoId);

        self::assertResponseStatusCodeSame(404);
    }
}
