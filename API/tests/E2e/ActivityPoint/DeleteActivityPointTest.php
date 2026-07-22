<?php

declare(strict_types=1);

namespace App\Tests\E2e\ActivityPoint;

final class DeleteActivityPointTest extends ActivityPointApiTestCase
{
    public function test_should_delete_activity_point(): void
    {
        $headers = $this->adminHeaders();
        $id = $this->insertActivityPoint();

        self::createClient()->request('DELETE', '/api/admin/activity-points/'.$id, [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/admin/activity-points/'.$id, [
            'headers' => $headers,
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_return_404_when_not_found(): void
    {
        $headers = $this->adminHeaders();

        self::createClient()->request('DELETE', '/api/admin/activity-points/00000000-0000-7000-8000-000000000000', [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_return_401_when_unauthenticated(): void
    {
        $id = $this->insertActivityPoint();

        self::createClient()->request('DELETE', '/api/admin/activity-points/'.$id);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');
        $id = $this->insertActivityPoint();

        self::createClient()->request('DELETE', '/api/admin/activity-points/'.$id, [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
