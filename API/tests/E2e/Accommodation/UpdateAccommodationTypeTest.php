<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationTypeTest extends AccommodationApiTestCase
{
    public function test_should_default_to_null(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        $response = self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertNull($response->toArray()['type'] ?? null);
    }

    public function test_should_update_type(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/type', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['type' => 'villa'],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);
        self::assertJsonContains(['type' => 'villa']);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/type', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['type' => 'villa'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/type', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['type' => 'villa'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_reject_unknown_type(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/type', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['type' => 'castle'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
