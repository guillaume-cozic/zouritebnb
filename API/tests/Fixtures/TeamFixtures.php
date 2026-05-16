<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Team\Infrastructure\Doctrine\TeamEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class TeamFixtures extends Fixture
{
    public const string DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    public function load(ObjectManager $manager): void
    {
        $id = Uuid::fromString(self::DEFAULT_TEAM_UUID);

        $existing = $manager->getRepository(TeamEntity::class)->find($id);
        if (null !== $existing) {
            return;
        }

        $team = (new TeamEntity())->setId($id);
        $manager->persist($team);
        $manager->flush();
    }
}
