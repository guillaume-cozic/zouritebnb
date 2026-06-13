<?php

declare(strict_types=1);

namespace App\Tests\E2e\SolidarityProject;

use App\Tests\E2e\AuthenticatedClientTrait;

final class AdminSolidarityProjectCollectionTest extends SolidarityProjectApiTestCase
{
    use AuthenticatedClientTrait;

    public function test_should_list_all_projects_including_closed_as_admin(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $this->insertSolidarityProject('Projet actif', 'Description active', null, 'active');
        $this->insertSolidarityProject('Projet clôturé', 'Description clôturée', null, 'closed');

        $response = self::createClient()->request('GET', '/api/admin/solidarity-projects', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame(2, $data['totalItems']);
        $statuses = array_column($data['member'], 'status');
        self::assertContains('active', $statuses);
        self::assertContains('closed', $statuses);
    }

    public function test_should_filter_admin_list_by_status(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $this->insertSolidarityProject('Projet actif', 'Description active', null, 'active');
        $this->insertSolidarityProject('Projet clôturé', 'Description clôturée', null, 'closed');

        $response = self::createClient()->request('GET', '/api/admin/solidarity-projects?status=closed', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertCount(1, $data['member']);
        self::assertSame('Projet clôturé', $data['member'][0]['title']);
    }

    public function test_should_create_a_solidarity_project(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);

        $response = self::createClient()->request('POST', '/api/admin/solidarity-projects', [
            'headers' => $this->authHeaders('admin@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Reforestation de Rodrigues',
                'description' => 'Plantation de 10 000 arbres endémiques.',
                'status' => 'active',
                'keyFigures' => [['value' => '10 000', 'label' => 'arbres plantés']],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        $created = $response->toArray();
        self::assertNotEmpty($created['id']);
        self::assertSame('Reforestation de Rodrigues', $created['title']);

        self::createClient()->request('GET', '/api/solidarity_projects/'.$created['id']);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $created['id'],
            'title' => 'Reforestation de Rodrigues',
            'status' => 'active',
            'isDefault' => false,
        ]);
    }

    public function test_should_reject_creation_with_blank_title(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);

        self::createClient()->request('POST', '/api/admin/solidarity-projects', [
            'headers' => $this->authHeaders('admin@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => '',
                'description' => 'Une description.',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_activate_and_deactivate_a_project(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $headers = $this->authHeaders('admin@example.com') + ['Content-Type' => 'application/merge-patch+json'];
        $id = $this->insertSolidarityProject('Projet actif', 'Description', null, 'active');

        self::createClient()->request('PATCH', '/api/admin/solidarity-projects/'.$id.'/status', [
            'headers' => $headers,
            'json' => ['status' => 'closed'],
        ]);
        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/solidarity_projects/'.$id);
        self::assertJsonContains(['id' => $id, 'status' => 'closed']);

        self::createClient()->request('PATCH', '/api/admin/solidarity-projects/'.$id.'/status', [
            'headers' => $headers,
            'json' => ['status' => 'active'],
        ]);
        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/solidarity_projects/'.$id);
        self::assertJsonContains(['id' => $id, 'status' => 'active']);
    }

    public function test_should_return_403_when_creating_as_non_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');

        self::createClient()->request('POST', '/api/admin/solidarity-projects', [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => ['title' => 'Projet', 'description' => 'Description'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_401_when_listing_unauthenticated(): void
    {
        self::createClient()->request('GET', '/api/admin/solidarity-projects');

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_get_a_single_project_as_admin(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $id = $this->insertSolidarityProject('Projet clôturé', 'Description', null, 'closed', null, [
            ['value' => '10 000', 'label' => 'arbres'],
        ]);

        $response = self::createClient()->request('GET', '/api/admin/solidarity-projects/'.$id, [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'title' => 'Projet clôturé',
            'status' => 'closed',
            'keyFigures' => [['value' => '10 000', 'label' => 'arbres']],
        ]);
    }

    public function test_should_return_404_when_getting_unknown_project(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);

        self::createClient()->request('GET', '/api/admin/solidarity-projects/00000000-0000-7000-8000-000000000000', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function test_should_update_a_project(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $id = $this->insertSolidarityProject('Ancien titre', 'Ancienne description', null, 'active');

        self::createClient()->request('PATCH', '/api/admin/solidarity-projects/'.$id, [
            'headers' => $this->authHeaders('admin@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'title' => 'Nouveau titre',
                'description' => 'Nouvelle description.',
                'imageUrl' => 'https://example.com/new.jpg',
                'status' => 'closed',
                'keyFigures' => [['value' => '5 ans', 'label' => 'de programme']],
            ],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('GET', '/api/admin/solidarity-projects/'.$id, [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);
        self::assertJsonContains([
            'id' => $id,
            'title' => 'Nouveau titre',
            'description' => 'Nouvelle description.',
            'imageUrl' => 'https://example.com/new.jpg',
            'status' => 'closed',
            'keyFigures' => [['value' => '5 ans', 'label' => 'de programme']],
        ]);
    }

    public function test_should_return_403_when_updating_as_non_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');
        $id = $this->insertSolidarityProject('Projet', 'Description', null, 'active');

        self::createClient()->request('PATCH', '/api/admin/solidarity-projects/'.$id, [
            'headers' => $this->authHeaders('host@example.com') + ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['title' => 'Hack', 'description' => 'Hack'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
