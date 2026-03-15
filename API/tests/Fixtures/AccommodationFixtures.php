<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AccommodationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $accommodations = [
            [
                'title' => 'Cozy Mountain Chalet',
                'description' => 'A warm and inviting chalet nestled in the mountains, perfect for a winter getaway. Features a fireplace, hot tub, and stunning panoramic views.',
                'price' => 150.00,
            ],
            [
                'title' => 'Beachfront Villa',
                'description' => 'Luxurious villa with direct beach access, private pool, and modern amenities. Ideal for families or groups looking for a tropical escape.',
                'price' => 320.00,
            ],
            [
                'title' => 'Downtown Studio Apartment',
                'description' => 'Compact and modern studio in the heart of the city. Walking distance to restaurants, museums, and public transport.',
                'price' => 85.00,
            ],
            [
                'title' => 'Lakeside Cabin',
                'description' => 'Rustic cabin on the shores of a peaceful lake. Includes a private dock, canoe, and barbecue area. Perfect for nature lovers.',
                'price' => 110.00,
            ],
            [
                'title' => 'Luxury Penthouse Suite',
                'description' => 'Elegant penthouse with floor-to-ceiling windows offering breathtaking city skyline views. Rooftop terrace and concierge service included.',
                'price' => 500.00,
            ],
        ];

        foreach ($accommodations as $data) {
            $accommodation = (new AccommodationEntity())
                ->setTitle($data['title'])
                ->setDescription($data['description'])
                ->setPrice($data['price']);

            $manager->persist($accommodation);
        }

        $manager->flush();
    }
}
