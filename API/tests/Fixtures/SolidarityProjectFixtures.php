<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\SolidarityProject\Infrastructure\Doctrine\SolidarityProjectEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class SolidarityProjectFixtures extends Fixture
{
    private const REFORESTATION_DESCRIPTION = <<<'HTML'
        <p>Rodrigues a perdu près de 95 % de sa forêt indigène depuis sa colonisation. Avec elle, ce sont des dizaines d'espèces endémiques — plantes, oiseaux, chauves-souris — qui ont vu leur habitat disparaître, et des sols entiers qui partent à la mer à chaque saison des pluies. Ce projet veut inverser la tendance, vallée par vallée.</p>
        <h2>Pourquoi replanter ?</h2>
        <p>Les arbres endémiques comme le bois d'olive, le bois puant ou le café marron sont adaptés depuis des millénaires au climat rodriguais. Leurs racines profondes retiennent les sols sur les pentes, leur canopée abrite la faune locale et leurs fruits nourrissent la roussette de Rodrigues, l'une des chauves-souris les plus rares au monde.</p>
        <figure><img src="https://images.unsplash.com/photo-1457530378978-8bac673b8062?w=1200&q=80" alt="Jeunes plants en pépinière" /><figcaption>La pépinière communautaire de Grande Montagne produit 4 000 plants par an.</figcaption></figure>
        <h2>Un programme en trois ans</h2>
        <p>La première année est consacrée à la production en pépinière et à la préparation des parcelles avec les familles riveraines. Les deux années suivantes alternent plantations en saison humide et entretien en saison sèche — désherbage, paillage, remplacement des plants perdus.</p>
        <ul><li>Production de 10 000 plants endémiques en pépinière communautaire</li><li>Restauration de 45 hectares sur les bassins versants prioritaires</li><li>Formation de 120 bénévoles aux techniques de plantation et de suivi</li></ul>
        <blockquote>« Chaque arbre planté ici, c'est un peu de notre île qu'on rend à nos enfants. » — Marie-Ange, responsable de la pépinière</blockquote>
        <p>Le projet est mené avec les associations locales et le service forestier de Rodrigues. Chaque contribution finance directement les plants, les outils et les journées de formation des équipes de terrain.</p>
        HTML;

    private const PROJECTS = [
        [
            'title' => 'Reforestation de l\'île Rodrigues',
            'description' => self::REFORESTATION_DESCRIPTION,
            'imageUrl' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=800&q=80',
            'status' => 'active',
            'daysAgo' => 10,
            'keyFigures' => [
                ['value' => '10 000', 'label' => 'arbres plantés'],
                ['value' => '45 ha', 'label' => 'restaurés'],
                ['value' => '120', 'label' => 'bénévoles formés'],
                ['value' => '3 ans', 'label' => 'de programme'],
            ],
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
                ->setCreatedAt(new \DateTimeImmutable(\sprintf('-%d days', $project['daysAgo'])))
                ->setKeyFigures($project['keyFigures'] ?? []);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}
