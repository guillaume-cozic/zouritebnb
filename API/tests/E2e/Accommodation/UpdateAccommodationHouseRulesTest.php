<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationHouseRulesTest extends AccommodationApiTestCase
{
    public function test_should_update_house_rules(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/house-rules', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'smokingAllowed' => false,
                'petsAllowed' => true,
                'partiesAllowed' => false,
                'houseRulesNotes' => 'Merci de retirer vos chaussures à l\'intérieur.',
            ],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'smokingAllowed' => false,
            'petsAllowed' => true,
            'partiesAllowed' => false,
            'houseRulesNotes' => 'Merci de retirer vos chaussures à l\'intérieur.',
        ]);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/house-rules', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['petsAllowed' => true],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/house-rules', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['petsAllowed' => true],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_reject_too_long_notes(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/house-rules', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['houseRulesNotes' => str_repeat('a', 1001)],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
