<?php

declare(strict_types=1);

namespace App\Tests\E2e\Geography;

final class GetLocalityCollectionTest extends GeographyApiTestCase
{
    public function test_should_list_localities(): void
    {
        $regionId = $this->insertRegion('RODRIGUES', 'Rodrigues');
        $this->insertLocality('Port Mathurin', $regionId);
        $this->insertLocality('Riviere Cocos', $regionId);

        $response = self::createClient()->request('GET', '/api/localities');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_expose_locality_fields(): void
    {
        $regionId = $this->insertRegion('RODRIGUES', 'Rodrigues');
        $id = $this->insertLocality('Port Mathurin', $regionId);

        self::createClient()->request('GET', '/api/localities');

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'member' => [
                [
                    'id' => $id,
                    'name' => 'Port Mathurin',
                    'regionId' => $regionId,
                ],
            ],
        ]);
    }

    public function test_should_sort_localities_by_name_ascending(): void
    {
        $regionId = $this->insertRegion('RODRIGUES', 'Rodrigues');
        $this->insertLocality('Saint Gabriel', $regionId);
        $this->insertLocality('Anse Aux Anglais', $regionId);
        $this->insertLocality('Port Mathurin', $regionId);

        $response = self::createClient()->request('GET', '/api/localities');

        self::assertResponseIsSuccessful();

        $names = array_map(static fn (array $locality) => $locality['name'], $response->toArray()['member']);

        self::assertSame(['Anse Aux Anglais', 'Port Mathurin', 'Saint Gabriel'], $names);
    }

    public function test_should_filter_localities_by_region_code(): void
    {
        $rodriguesId = $this->insertRegion('RODRIGUES', 'Rodrigues');
        $mauritiusId = $this->insertRegion('MAURITIUS', 'Mauritius');
        $this->insertLocality('Port Mathurin', $rodriguesId);
        $this->insertLocality('Port Louis', $mauritiusId);

        $response = self::createClient()->request('GET', '/api/localities?regionCode=RODRIGUES');

        self::assertResponseIsSuccessful();

        $members = $response->toArray()['member'];

        self::assertCount(1, $members);
        self::assertSame('Port Mathurin', $members[0]['name']);
        self::assertSame($rodriguesId, $members[0]['regionId']);
    }

    public function test_should_return_all_localities_when_region_code_is_empty(): void
    {
        $regionId = $this->insertRegion('RODRIGUES', 'Rodrigues');
        $this->insertLocality('Port Mathurin', $regionId);
        $this->insertLocality('Riviere Cocos', $regionId);

        $response = self::createClient()->request('GET', '/api/localities?regionCode=');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_return_empty_collection_when_region_code_matches_nothing(): void
    {
        $regionId = $this->insertRegion('RODRIGUES', 'Rodrigues');
        $this->insertLocality('Port Mathurin', $regionId);

        $response = self::createClient()->request('GET', '/api/localities?regionCode=UNKNOWN');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }

    public function test_should_return_empty_collection(): void
    {
        $response = self::createClient()->request('GET', '/api/localities');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }
}
