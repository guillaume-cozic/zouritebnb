<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Accommodation\Infrastructure\Doctrine\PhotoEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Uid\Uuid;

class AccommodationFixtures extends Fixture
{
    public const string DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    private const AMENITY_CODES = [
        'wifi', 'air_conditioning', 'heating', 'fan', 'fireplace', 'safe', 'iron',
        'equipped_kitchen', 'fridge', 'oven', 'microwave', 'dishwasher', 'coffee_maker',
        'hair_dryer', 'bathtub', 'walk_in_shower', 'towels', 'toiletries', 'washing_machine',
        'bed_linen', 'extra_pillows', 'blankets', 'blackout_curtains',
        'garden', 'terrace', 'balcony', 'barbecue', 'outdoor_furniture',
        'private_pool', 'shared_pool', 'hot_tub', 'private_parking',
        'tv', 'streaming', 'books', 'board_games',
        'self_checkin', 'workspace',
        'sea_view', 'mountain_view', 'lake_view', 'beachfront', 'quiet_area', 'pets_allowed',
    ];

    private const VACATION_TEMPLATES = [
        [
            'title' => 'Villa Vue Océan',
            'description' => 'Magnifique villa avec vue imprenable sur l\'océan Indien. Terrasse privée avec piscine à débordement et accès direct à la plage de sable blanc. Idéale pour un séjour en famille ou entre amis.',
            'city' => 'Port Mathurin',
            'country' => 'Île Rodrigues',
            'image' => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=800&q=80',
        ],
        [
            'title' => 'Bungalow Plage Privée',
            'description' => 'Bungalow confortable situé sur une plage privée bordée de cocotiers. Parfait pour un séjour romantique avec coucher de soleil spectaculaire chaque soir.',
            'city' => 'Anse aux Anglais',
            'country' => 'Île Rodrigues',
            'image' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800&q=80',
        ],
        [
            'title' => 'Maison Traditionnelle Créole',
            'description' => 'Authentique maison rodriguaise rénovée avec goût. Jardin tropical luxuriant et vue panoramique sur les montagnes. Découvrez la culture locale dans un cadre authentique.',
            'city' => 'Mont Lubin',
            'country' => 'Île Rodrigues',
            'image' => 'https://images.unsplash.com/photo-1510798831971-661eb04b3739?w=800&q=80',
        ],
        [
            'title' => 'Éco-Lodge Premium',
            'description' => 'Lodge écologique de luxe intégré harmonieusement dans la nature. Panneaux solaires, récupération d\'eau de pluie et matériaux locaux. Une expérience unique et respectueuse de l\'environnement.',
            'city' => 'Graviers',
            'country' => 'Île Rodrigues',
            'image' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&q=80',
        ],
        [
            'title' => 'Appartement Centre-Ville',
            'description' => 'Appartement moderne et lumineux au cœur de Port Mathurin. Proche du marché, des restaurants et de toutes les commodités. Terrasse avec vue sur le port.',
            'city' => 'Port Mathurin',
            'country' => 'Île Rodrigues',
            'image' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&q=80',
        ],
        [
            'title' => 'Villa Lagon',
            'description' => 'Villa luxueuse avec piscine à débordement et vue panoramique sur le lagon turquoise. Suite parentale avec jacuzzi, cuisine équipée et personnel de maison.',
            'city' => 'Pointe Coton',
            'country' => 'Île Rodrigues',
            'image' => 'https://images.unsplash.com/photo-1582268611958-ebfd161ef9cf?w=800&q=80',
        ],
    ];

    public function __construct(
        private readonly string $uploadDir,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0o755, true);
        }

        foreach (self::VACATION_TEMPLATES as $template) {
            $accommodationId = Uuid::v7();

            $accommodation = (new AccommodationEntity())
                ->setId($accommodationId)
                ->setTeamId(Uuid::fromString(self::DEFAULT_TEAM_UUID))
                ->setTitle($template['title'])
                ->setDescription($template['description'])
                ->setPrice($faker->randomFloat(0, 50, 500))
                ->setStatus('published')
                ->setStreet($faker->streetAddress())
                ->setCity($template['city'])
                ->setZipCode($faker->postcode())
                ->setCountry($template['country'])
                ->setLatitude(-19.7 + $faker->randomFloat(4, -0.05, 0.05))
                ->setLongitude(63.4 + $faker->randomFloat(4, -0.05, 0.05))
                ->setBedrooms($faker->numberBetween(1, 5))
                ->setBathrooms($faker->numberBetween(1, 3))
                ->setMaxGuests($faker->numberBetween(2, 12))
                ->setSingleBeds($faker->numberBetween(0, 4))
                ->setDoubleBeds($faker->numberBetween(1, 3))
                ->setAmenities($faker->randomElements(self::AMENITY_CODES, $faker->numberBetween(5, 15)));

            $manager->persist($accommodation);

            // Download photo and create PhotoEntity
            $photoId = Uuid::v7();
            $filename = $photoId->toRfc4122().'.jpg';
            $filePath = $this->uploadDir.'/'.$filename;

            $imageData = @file_get_contents($template['image']);
            if (false !== $imageData) {
                file_put_contents($filePath, $imageData);
            }

            $photo = (new PhotoEntity())
                ->setId($photoId)
                ->setAccommodationId($accommodationId)
                ->setFilename($filename)
                ->setOriginalName($template['title'].'.jpg')
                ->setMimeType('image/jpeg')
                ->setSize($imageData ? \strlen($imageData) : 0);

            $manager->persist($photo);
        }

        $manager->flush();
    }
}
