<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class SolidarityProjectFixtures extends Fixture
{
    private const PROJECTS = [
        [
            'title' => 'Reforestation de l\'île Rodrigues',
            'description' => 'Plantation de 10 000 arbres endémiques sur trois ans pour restaurer la biodiversité locale et protéger les sols contre l\'érosion.',
            'imageUrl' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=800&q=80',
            'status' => 'active',
            'daysAgo' => 10,
        ],
        [
            'title' => 'Soutien aux pêcheurs artisanaux',
            'description' => 'Programme d\'aide aux pêcheurs locaux : équipement durable, formation à la pêche raisonnée et reconstruction des pirogues traditionnelles.',
            'imageUrl' => 'https://images.unsplash.com/photo-1502085671122-2d218cd434e6?w=800&q=80',
            'status' => 'active',
            'daysAgo' => 30,
        ],
        [
            'title' => 'École ouverte pour tous',
            'description' => 'Financement de fournitures scolaires et de repas chauds pour 200 enfants des villages reculés de l\'île.',
            'imageUrl' => 'https://images.unsplash.com/photo-1588072432836-e10032774350?w=800&q=80',
            'status' => 'active',
            'daysAgo' => 60,
        ],
        [
            'title' => 'Récif corallien préservé',
            'description' => 'Projet de restauration du récif corallien mené en partenariat avec les ONG locales. Campagne clôturée avec succès.',
            'imageUrl' => 'https://images.unsplash.com/photo-1582967788606-a171c1080cb0?w=800&q=80',
            'status' => 'closed',
            'daysAgo' => 365,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PROJECTS as $project) {
            $entity = new SolidarityProjectEntity()
                ->setId(Uuid::v7())
                ->setTitle($project['title'])
                ->setDescription($project['description'])
                ->setImageUrl($project['imageUrl'])
                ->setStatus($project['status'])
                ->setCreatedAt(new \DateTimeImmutable(\sprintf('-%d days', $project['daysAgo'])));

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
