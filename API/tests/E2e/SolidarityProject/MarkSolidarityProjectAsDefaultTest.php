<?php

declare(strict_types=1);

namespace App\Tests\E2e\SolidarityProject;

use App\Tests\E2e\AuthenticatedClientTrait;

final class MarkSolidarityProjectAsDefaultTest extends SolidarityProjectApiTestCase
{
    use AuthenticatedClientTrait;

    public function test_should_mark_solidarity_project_as_default(): void
    {
        $this->createAuthUser(email: 'host@example.com');
        $id = $this->insertSolidarityProject('Reforestation', 'Plant 10 000 trees', null, 'active');

        self::createClient()->request('PATCH', '/api/solidarity_projects/'.$id.'/mark-default', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/solidarity_projects/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'isDefault' => true,
        ]);
    }

    public function test_should_unmark_the_previous_default_project(): void
    {
        $this->createAuthUser(email: 'host@example.com');
        $headers = $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'];

        $previousDefaultId = $this->insertSolidarityProject(
            'Ancien projet',
            'Projet par défaut historique',
            null,
            'active',
            new \DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        );

        self::createClient()->request('PATCH', '/api/solidarity_projects/'.$previousDefaultId.'/mark-default', [
            'headers' => $headers,
            'json' => [],
        ]);
        self::assertResponseStatusCodeSame(204);

        $newDefaultId = $this->insertSolidarityProject(
            'Nouveau projet',
            'Nouveau projet par défaut',
            null,
            'active',
            new \DateTimeImmutable('2026-02-01T10:00:00+00:00'),
        );

        self::createClient()->request('PATCH', '/api/solidarity_projects/'.$newDefaultId.'/mark-default', [
            'headers' => $headers,
            'json' => [],
        ]);
        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/solidarity_projects/'.$newDefaultId);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $newDefaultId,
            'isDefault' => true,
        ]);

        self::createClient()->request('GET', '/api/solidarity_projects/'.$previousDefaultId);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $previousDefaultId,
            'isDefault' => false,
        ]);
    }

    public function test_should_return422_when_project_does_not_exist(): void
    {
        $this->createAuthUser(email: 'host@example.com');

        self::createClient()->request('PATCH', '/api/solidarity_projects/00000000-0000-0000-0000-000000000000/mark-default', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $id = $this->insertSolidarityProject('Reforestation', 'Plant 10 000 trees', null, 'active');

        self::createClient()->request('PATCH', '/api/solidarity_projects/'.$id.'/mark-default', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_not_mark_as_default_when_not_authenticated(): void
    {
        $id = $this->insertSolidarityProject('Reforestation', 'Plant 10 000 trees', null, 'active');

        self::createClient()->request('PATCH', '/api/solidarity_projects/'.$id.'/mark-default', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [],
        ]);
        self::assertResponseStatusCodeSame(401);

        self::createClient()->request('GET', '/api/solidarity_projects/'.$id);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'isDefault' => false,
        ]);
    }
}
