<?php

declare(strict_types=1);

namespace App\Tests\E2e\Geography;

final class GetRegionCollectionTest extends GeographyApiTestCase
{
    public function test_should_list_regions(): void
    {
        $this->insertRegion('RODRIGUES', 'Rodrigues');
        $this->insertRegion('MAURITIUS', 'Mauritius');

        $response = self::createClient()->request('GET', '/api/regions');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_expose_region_fields(): void
    {
        $id = $this->insertRegion('RODRIGUES', 'Rodrigues');

        self::createClient()->request('GET', '/api/regions');

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'member' => [
                [
                    'id' => $id,
                    'code' => 'RODRIGUES',
                    'name' => 'Rodrigues',
                ],
            ],
        ]);
    }

    public function test_should_sort_regions_by_name_ascending(): void
    {
        $this->insertRegion('ZONE', 'Zanzibar');
        $this->insertRegion('ALPHA', 'Alpha');
        $this->insertRegion('MID', 'Mauritius');

        $response = self::createClient()->request('GET', '/api/regions');

        self::assertResponseIsSuccessful();

        $names = array_map(static fn (array $region) => $region['name'], $response->toArray()['member']);

        self::assertSame(['Alpha', 'Mauritius', 'Zanzibar'], $names);
    }

    public function test_should_return_empty_collection(): void
    {
        $response = self::createClient()->request('GET', '/api/regions');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }
}
