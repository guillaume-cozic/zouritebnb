<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class DeleteAccommodationPhotoTest extends AccommodationApiTestCase
{
    public function test_should_delete_photo(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $accommodationId = $this->insertAccommodation('Chalet', 'A cozy chalet', 150.0);
        $photoId = $this->insertPhoto($accommodationId);

        self::createClient()->request('DELETE', '/api/accommodations/'.$accommodationId.'/photos/'.$photoId, [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(204);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $accommodationId = $this->insertAccommodation('Chalet', 'A cozy chalet', 150.0);
        $photoId = $this->insertPhoto($accommodationId);

        self::createClient()->request('DELETE', '/api/accommodations/'.$accommodationId.'/photos/'.$photoId);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $accommodationId = $this->insertAccommodation('Chalet', 'A cozy chalet', 150.0);
        $photoId = $this->insertPhoto($accommodationId);

        self::createClient()->request('DELETE', '/api/accommodations/'.$accommodationId.'/photos/'.$photoId, [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_not_delete_when_photo_not_found(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $accommodationId = $this->insertAccommodation('Chalet', 'A cozy chalet', 150.0);

        self::createClient()->request('DELETE', '/api/accommodations/'.$accommodationId.'/photos/00000000-0000-0000-0000-000000000000', [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_not_delete_photo_from_wrong_accommodation(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $accommodationId1 = $this->insertAccommodation('Chalet 1', 'First chalet', 150.0);
        $accommodationId2 = $this->insertAccommodation('Chalet 2', 'Second chalet', 200.0);
        $photoId = $this->insertPhoto($accommodationId1);

        self::createClient()->request('DELETE', '/api/accommodations/'.$accommodationId2.'/photos/'.$photoId, [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
