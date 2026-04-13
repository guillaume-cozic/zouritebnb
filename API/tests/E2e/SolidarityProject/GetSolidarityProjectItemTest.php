<?php

declare(strict_types=1);

namespace App\Tests\E2e\SolidarityProject;

final class GetSolidarityProjectItemTest extends SolidarityProjectApiTestCase
{
    public function testShouldGetSolidarityProject(): void
    {
        $id = $this->insertSolidarityProject(
            'Reforestation',
            'Plant 10 000 trees',
            'https://example.com/image.jpg',
            'active',
        );

        self::createClient()->request('GET', '/api/solidarity_projects/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'title' => 'Reforestation',
            'description' => 'Plant 10 000 trees',
            'imageUrl' => 'https://example.com/image.jpg',
            'status' => 'active',
        ]);
    }

    public function testShouldGetClosedProjectById(): void
    {
        $id = $this->insertSolidarityProject('Old', 'desc', null, 'closed');

        self::createClient()->request('GET', '/api/solidarity_projects/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'status' => 'closed',
        ]);
    }

    public function testShouldReturn404WhenNotFound(): void
    {
        self::createClient()->request('GET', '/api/solidarity_projects/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
