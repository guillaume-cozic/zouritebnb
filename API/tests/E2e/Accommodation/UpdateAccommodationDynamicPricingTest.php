<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationDynamicPricingTest extends AccommodationApiTestCase
{
    public function test_should_update_dynamic_pricing(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/dynamic-pricing', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['weekendSurchargePercentage' => 20.0, 'lastMinuteDiscountPercentage' => 15.0, 'lastMinuteDays' => 7],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'weekendSurchargePercentage' => 20,
            'lastMinuteDiscountPercentage' => 15,
            'lastMinuteDays' => 7,
        ]);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/dynamic-pricing', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['weekendSurchargePercentage' => 20.0],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/dynamic-pricing', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['weekendSurchargePercentage' => 20.0],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_reject_incomplete_last_minute(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/dynamic-pricing', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['lastMinuteDiscountPercentage' => 15.0],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
