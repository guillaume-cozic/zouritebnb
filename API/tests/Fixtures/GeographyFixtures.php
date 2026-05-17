<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Geography\Infrastructure\Doctrine\LocalityEntity;
use App\Geography\Infrastructure\Doctrine\RegionEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class GeographyFixtures extends Fixture
{
    public const string RODRIGUES_REGION_UUID = '00000000-0000-4000-8000-00000000000a';

    private const RODRIGUES_LOCALITIES = [
        'Port Mathurin',
        'Mont Lubin',
        'La Ferme',
        'Rivière Cocos',
        'Anse aux Anglais',
        'Pointe Coton',
        'Saint François',
        'Baie aux Huîtres',
        'Baie du Nord',
        'Baladirou',
        'Camp du Roi',
        'Citronnelle',
        'Crève Cœur',
        'Grand Baie',
        'Graviers',
        'Mangues',
        'Petit Gabriel',
        'Petite Butte',
        'Plaine Corail',
        'Quatre-Vents',
        'Roche Bon Dieu',
        'Songes',
        'Trèfles',
        'Trou d\'Argent',
    ];

    public function load(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(RegionEntity::class)->findOneBy(['code' => 'RODRIGUES']);
        if (null !== $existing) {
            return;
        }

        $regionId = Uuid::fromString(self::RODRIGUES_REGION_UUID);
        $region = new RegionEntity()
            ->setId($regionId)
            ->setCode('RODRIGUES')
            ->setName('Rodrigues');

        $manager->persist($region);

        foreach (self::RODRIGUES_LOCALITIES as $name) {
            $locality = new LocalityEntity()
                ->setId(Uuid::v7())
                ->setName($name)
                ->setRegionId($regionId);

            $manager->persist($locality);
        }

        $manager->flush();
    }
}
