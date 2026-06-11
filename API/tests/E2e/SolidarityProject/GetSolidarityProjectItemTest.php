<?php

declare(strict_types=1);

namespace App\Tests\E2e\SolidarityProject;

final class GetSolidarityProjectItemTest extends SolidarityProjectApiTestCase
{
    public function test_should_get_solidarity_project(): void
    {
        $id = $this->insertSolidarityProject(
            'Reforestation',
            'Plant 10 000 trees',
            'https://example.com/image.jpg',
            'active',
            keyFigures: [
                ['value' => '10 000', 'label' => 'arbres plantés'],
                ['value' => '3 ans', 'label' => 'de programme'],
            ],
        );

        self::createClient()->request('GET', '/api/solidarity_projects/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'title' => 'Reforestation',
            'description' => 'Plant 10 000 trees',
            'imageUrl' => 'https://example.com/image.jpg',
            'status' => 'active',
            'keyFigures' => [
                ['value' => '10 000', 'label' => 'arbres plantés'],
                ['value' => '3 ans', 'label' => 'de programme'],
            ],
        ]);
    }

    public function test_should_return_empty_key_figures_when_none_defined(): void
    {
        $id = $this->insertSolidarityProject('Reforestation', 'Plant trees');

        self::createClient()->request('GET', '/api/solidarity_projects/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['keyFigures' => []]);
    }

    public function test_should_get_closed_project_by_id(): void
    {
        $id = $this->insertSolidarityProject('Old', 'desc', null, 'closed');

        self::createClient()->request('GET', '/api/solidarity_projects/'.$id);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'status' => 'closed',
        ]);
    }

    public function test_should_return404_when_not_found(): void
    {
        self::createClient()->request('GET', '/api/solidarity_projects/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }
}
