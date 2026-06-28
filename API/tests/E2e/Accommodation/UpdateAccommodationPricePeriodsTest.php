<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationPricePeriodsTest extends AccommodationApiTestCase
{
    public function test_should_replace_price_periods(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/price-periods', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['pricePeriods' => [
                ['startDate' => '2026-07-01', 'endDate' => '2026-08-31', 'pricePerNight' => 250.0],
            ]],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'pricePeriods' => [
                ['startDate' => '2026-07-01', 'endDate' => '2026-08-31', 'pricePerNight' => 250],
            ],
        ]);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/price-periods', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['pricePeriods' => []],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_user_is_not_owner(): void
    {
        $this->createAuthUser(email: 'intruder@example.com', teamId: '019cf27a-96ba-7957-8622-aaaaaaaaaaaa');
        $headers = $this->authHeaders('intruder@example.com');
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/price-periods', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['pricePeriods' => []],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_reject_invalid_range(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PUT', '/api/accommodations/'.$id.'/price-periods', [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['pricePeriods' => [
                ['startDate' => '2026-08-31', 'endDate' => '2026-07-01', 'pricePerNight' => 250.0],
            ]],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
