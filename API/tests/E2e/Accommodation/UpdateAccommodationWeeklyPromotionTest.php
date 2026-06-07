<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationWeeklyPromotionTest extends AccommodationApiTestCase
{
    public function test_should_update_weekly_promotion(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/weekly-promotion', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['weeklyPromotionPercentage' => 10.0],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['weeklyPromotionPercentage' => 10]);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/weekly-promotion', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['weeklyPromotionPercentage' => 10.0],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/weekly-promotion', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['weeklyPromotionPercentage' => 10.0],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_reject_out_of_bounds_promotion(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/weekly-promotion', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['weeklyPromotionPercentage' => 150.0],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
