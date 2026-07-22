<?php

declare(strict_types=1);

namespace App\Tests\E2e\ActivityPoint;

final class GetActivityPointCollectionTest extends ActivityPointApiTestCase
{
    public function test_should_list_all_points_without_authentication(): void
    {
        $this->insertActivityPoint(name: 'Lagune de Mourouk', category: 'kitesurf', articleUrl: '/blog/kitesurf-mourouk');
        $this->insertActivityPoint(name: 'Grande Montagne', description: 'Réserve naturelle au sommet de l\'île.', category: 'nature', latitude: -19.6833, longitude: 63.4333);

        $response = self::createClient()->request('GET', '/api/activity-points');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(2, $members);
        self::assertSame('Grande Montagne', $members[0]['name']);
        self::assertSame('Lagune de Mourouk', $members[1]['name']);
        self::assertJsonContains([
            'member' => [
                [
                    'name' => 'Grande Montagne',
                    'description' => 'Réserve naturelle au sommet de l\'île.',
                    'category' => 'nature',
                    'latitude' => -19.6833,
                    'longitude' => 63.4333,
                    'articleUrl' => null,
                ],
                [
                    'name' => 'Lagune de Mourouk',
                    'category' => 'kitesurf',
                    'articleUrl' => '/blog/kitesurf-mourouk',
                ],
            ],
        ]);
    }

    public function test_should_return_empty_collection(): void
    {
        $response = self::createClient()->request('GET', '/api/activity-points');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }
}
