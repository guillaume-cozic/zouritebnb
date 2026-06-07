<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UnpublishAccommodationTest extends AccommodationApiTestCase
{
    public function test_should_unpublish_accommodation(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0, 'published');

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/unpublish', [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(204);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0, 'published');

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/unpublish');

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0, 'published');

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/unpublish', [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_not_unpublish_unknown_accommodation(): void
    {
        $headers = $this->authenticatedOwnerHeaders();

        self::createClient()->request('PATCH', '/api/accommodations/01961e2f-dead-7000-beef-000000000099/unpublish', [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
