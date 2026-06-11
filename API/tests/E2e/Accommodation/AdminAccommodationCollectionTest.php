<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class AdminAccommodationCollectionTest extends AccommodationApiTestCase
{
    public function test_should_list_all_accommodations_as_admin(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $this->createAuthUser(email: 'host@example.com', teamId: self::OWNER_TEAM_ID);

        $villaId = $this->insertAccommodation('Villa du lagon', 'Une villa avec vue sur le lagon.', 120.0, 'published');
        $bungalowId = $this->insertAccommodation('Bungalow de la plage', 'Un bungalow les pieds dans l\'eau.', 80.0);

        $response = self::createClient()->request('GET', '/api/admin/accommodations', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
        self::assertJsonContains([
            'member' => [
                [
                    'id' => $bungalowId,
                    'title' => 'Bungalow de la plage',
                    'status' => 'draft',
                    'price' => 80,
                    'city' => null,
                    'bedrooms' => null,
                    'maxGuests' => null,
                    'weeklyPromotionPercentage' => null,
                    'teamId' => self::OWNER_TEAM_ID,
                    'hostEmail' => 'host@example.com',
                ],
                [
                    'id' => $villaId,
                    'title' => 'Villa du lagon',
                    'status' => 'published',
                    'price' => 120,
                    'teamId' => self::OWNER_TEAM_ID,
                    'hostEmail' => 'host@example.com',
                ],
            ],
        ]);
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');

        self::createClient()->request('GET', '/api/admin/accommodations', [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        self::createClient()->request('GET', '/api/admin/accommodations');

        self::assertResponseStatusCodeSame(401);
    }
}
