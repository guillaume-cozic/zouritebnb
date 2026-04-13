<?php

declare(strict_types=1);

namespace App\Tests\E2e\SolidarityProject;

final class GetSolidarityProjectCollectionTest extends SolidarityProjectApiTestCase
{
    public function testShouldListActiveSolidarityProjects(): void
    {
        $this->insertSolidarityProject('Project A', 'Description A', null, 'active', new \DateTimeImmutable('2026-01-01'));
        $this->insertSolidarityProject('Project B', 'Description B', null, 'active', new \DateTimeImmutable('2026-03-01'));

        $response = self::createClient()->request('GET', '/api/solidarity_projects');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function testShouldNotListClosedProjects(): void
    {
        $this->insertSolidarityProject('Active project', 'desc', null, 'active');
        $this->insertSolidarityProject('Closed project', 'desc', null, 'closed');

        $response = self::createClient()->request('GET', '/api/solidarity_projects');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Active project', $members[0]['title']);
    }

    public function testShouldReturnProjectsOrderedByCreatedAtDesc(): void
    {
        $this->insertSolidarityProject('Older', 'desc', null, 'active', new \DateTimeImmutable('2026-01-01'));
        $this->insertSolidarityProject('Newer', 'desc', null, 'active', new \DateTimeImmutable('2026-03-01'));

        $response = self::createClient()->request('GET', '/api/solidarity_projects');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertSame('Newer', $members[0]['title']);
        self::assertSame('Older', $members[1]['title']);
    }

    public function testShouldReturnEmptyCollection(): void
    {
        $response = self::createClient()->request('GET', '/api/solidarity_projects');

        self::assertResponseIsSuccessful();
        self::assertSame([], $response->toArray()['member']);
    }
}
