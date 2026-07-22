<?php

declare(strict_types=1);

namespace App\Tests\E2e\ActivityPoint;

final class AdminGetActivityPointTest extends ActivityPointApiTestCase
{
    public function test_should_get_a_single_point_as_admin(): void
    {
        $headers = $this->adminHeaders();
        $id = $this->insertActivityPoint(
            name: 'Lagune de Mourouk',
            description: 'Spot de kitesurf au lagon turquoise.',
            category: 'kitesurf',
            latitude: -19.7577,
            longitude: 63.4499,
            articleUrl: '/blog/kitesurf-mourouk',
        );

        self::createClient()->request('GET', '/api/admin/activity-points/'.$id, [
            'headers' => $headers,
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'name' => 'Lagune de Mourouk',
            'description' => 'Spot de kitesurf au lagon turquoise.',
            'category' => 'kitesurf',
            'latitude' => -19.7577,
            'longitude' => 63.4499,
            'articleUrl' => '/blog/kitesurf-mourouk',
        ]);
    }

    public function test_should_return_404_when_not_found(): void
    {
        $headers = $this->adminHeaders();

        self::createClient()->request('GET', '/api/admin/activity-points/00000000-0000-7000-8000-000000000000', [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
