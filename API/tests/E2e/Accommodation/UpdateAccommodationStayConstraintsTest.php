<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationStayConstraintsTest extends AccommodationApiTestCase
{
    public function test_should_default_to_null(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        $response = self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertNull($data['minNights'] ?? null);
        self::assertNull($data['maxNights'] ?? null);
    }

    public function test_should_update_stay_constraints(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/stay-constraints', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['minNights' => 2, 'maxNights' => 30],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);
        self::assertJsonContains(['minNights' => 2, 'maxNights' => 30]);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/stay-constraints', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['minNights' => 2, 'maxNights' => 30],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/stay-constraints', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['minNights' => 2, 'maxNights' => 30],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_reject_min_greater_than_max(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/stay-constraints', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['minNights' => 10, 'maxNights' => 5],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_reject_non_positive(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/stay-constraints', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['minNights' => 0, 'maxNights' => null],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
