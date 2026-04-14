<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

final class UpdateAccommodationWeeklyPromotionTest extends AccommodationApiTestCase
{
    public function testShouldUpdateWeeklyPromotion(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/weekly-promotion', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['weeklyPromotionPercentage' => 10.0],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/accommodations/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['weeklyPromotionPercentage' => 10]);
    }

    public function testShouldRejectOutOfBoundsPromotion(): void
    {
        $id = $this->insertAccommodation('Cozy Chalet', 'A warm mountain chalet', 150.0);

        self::createClient()->request('PATCH', '/api/accommodations/'.$id.'/weekly-promotion', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['weeklyPromotionPercentage' => 150.0],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
