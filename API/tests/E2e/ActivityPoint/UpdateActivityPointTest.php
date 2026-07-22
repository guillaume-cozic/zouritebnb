<?php

declare(strict_types=1);

namespace App\Tests\E2e\ActivityPoint;

final class UpdateActivityPointTest extends ActivityPointApiTestCase
{
    public function test_should_update_activity_point(): void
    {
        $headers = $this->adminHeaders();
        $id = $this->insertActivityPoint(name: 'Ancien nom', description: 'Ancienne description.', category: 'nature');

        self::createClient()->request('PATCH', '/api/admin/activity-points/'.$id, [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Trou d\'Argent',
                'description' => 'Plage sauvage accessible à pied.',
                'category' => 'beach',
                'latitude' => -19.74,
                'longitude' => 63.47,
                'articleUrl' => 'https://example.com/trou-d-argent',
            ],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/admin/activity-points/'.$id, [
            'headers' => $headers,
        ]);
        self::assertJsonContains([
            'id' => $id,
            'name' => 'Trou d\'Argent',
            'description' => 'Plage sauvage accessible à pied.',
            'category' => 'beach',
            'latitude' => -19.74,
            'longitude' => 63.47,
            'articleUrl' => 'https://example.com/trou-d-argent',
        ]);
    }

    public function test_should_return_404_when_not_found(): void
    {
        $headers = $this->adminHeaders();

        self::createClient()->request('PATCH', '/api/admin/activity-points/00000000-0000-7000-8000-000000000000', [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Point',
                'description' => 'Description.',
                'category' => 'nature',
                'latitude' => -19.7,
                'longitude' => 63.4,
            ],
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_return_422_when_payload_is_invalid(): void
    {
        $headers = $this->adminHeaders();
        $id = $this->insertActivityPoint();

        self::createClient()->request('PATCH', '/api/admin/activity-points/'.$id, [
            'headers' => $headers + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Trou d\'Argent',
                'description' => 'Plage sauvage.',
                'category' => 'beach',
                'latitude' => -19.74,
                'longitude' => 57.5,
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        $id = $this->insertActivityPoint();

        self::createClient()->request('PATCH', '/api/admin/activity-points/'.$id, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Point'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');
        $id = $this->insertActivityPoint();

        self::createClient()->request('PATCH', '/api/admin/activity-points/'.$id, [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'Point'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
