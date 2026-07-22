<?php

declare(strict_types=1);

namespace App\Tests\E2e\ActivityPoint;

final class AdminGetActivityPointCollectionTest extends ActivityPointApiTestCase
{
    public function test_should_list_points_as_admin(): void
    {
        $headers = $this->adminHeaders();
        $this->insertActivityPoint(name: 'Lagune de Mourouk', category: 'kitesurf');
        $this->insertActivityPoint(name: 'Trou d\'Argent', description: 'Plage sauvage accessible à pied.', category: 'beach', latitude: -19.74, longitude: 63.47);

        $response = self::createClient()->request('GET', '/api/admin/activity-points', [
            'headers' => $headers,
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame(2, $data['totalItems']);
        self::assertSame('Lagune de Mourouk', $data['member'][0]['name']);
        self::assertSame('Trou d\'Argent', $data['member'][1]['name']);
    }

    public function test_should_filter_by_search_on_name_and_description(): void
    {
        $headers = $this->adminHeaders();
        $this->insertActivityPoint(name: 'Lagune de Mourouk', description: 'Spot de kitesurf.', category: 'kitesurf');
        $this->insertActivityPoint(name: 'Trou d\'Argent', description: 'Plage sauvage.', category: 'beach', latitude: -19.74, longitude: 63.47);

        $response = self::createClient()->request('GET', '/api/admin/activity-points?search=sauvage', [
            'headers' => $headers,
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertCount(1, $data['member']);
        self::assertSame('Trou d\'Argent', $data['member'][0]['name']);
    }

    public function test_should_filter_by_category(): void
    {
        $headers = $this->adminHeaders();
        $this->insertActivityPoint(name: 'Lagune de Mourouk', category: 'kitesurf');
        $this->insertActivityPoint(name: 'Caverne Patate', description: 'Grotte calcaire.', category: 'heritage', latitude: -19.75, longitude: 63.38);

        $response = self::createClient()->request('GET', '/api/admin/activity-points?category=heritage', [
            'headers' => $headers,
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertCount(1, $data['member']);
        self::assertSame('Caverne Patate', $data['member'][0]['name']);
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        self::createClient()->request('GET', '/api/admin/activity-points');

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');

        self::createClient()->request('GET', '/api/admin/activity-points', [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
