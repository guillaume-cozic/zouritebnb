<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use Symfony\Component\Uid\Uuid;

final class GetMyAccommodationsCollectionTest extends AccommodationApiTestCase
{
    public function test_should_list_only_accommodations_of_the_current_team(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $this->insertAccommodation('My Villa', 'Mine', 320.0, 'published');
        $this->insertAccommodation('My Cabin', 'Mine too', 110.0, 'draft');
        // Another team's accommodation must never leak into the back-office listing.
        $this->insertAccommodation('Other Villa', 'Not mine', 200.0, 'published', teamId: Uuid::v7()->toRfc4122());

        $response = self::createClient()->request('GET', '/api/my-accommodations', ['headers' => $headers]);

        self::assertResponseIsSuccessful();
        $titles = array_column($response->toArray()['member'], 'title');
        sort($titles);
        self::assertSame(['My Cabin', 'My Villa'], $titles);
    }

    public function test_should_include_drafts_unlike_public_catalog(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $this->insertAccommodation('Draft Villa', 'A draft', 100.0, 'draft');

        $response = self::createClient()->request('GET', '/api/my-accommodations', ['headers' => $headers]);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $response->toArray()['member']);
        self::assertSame('Draft Villa', $response->toArray()['member'][0]['title']);
    }

    public function test_should_filter_by_status(): void
    {
        $headers = $this->authenticatedOwnerHeaders();
        $this->insertAccommodation('Published One', 'Pub', 100.0, 'published');
        $this->insertAccommodation('Draft One', 'Draft', 150.0, 'draft');

        $response = self::createClient()->request('GET', '/api/my-accommodations?status=draft', ['headers' => $headers]);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $response->toArray()['member']);
        self::assertSame('Draft One', $response->toArray()['member'][0]['title']);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        $this->insertAccommodation('Some Villa', 'Whatever', 100.0, 'published');

        self::createClient()->request('GET', '/api/my-accommodations');

        self::assertResponseStatusCodeSame(401);
    }
}
