<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Exercises the map bounding-box ("search this area") filter of
 * {@see \App\Accommodation\Infrastructure\ApiPlatform\PublishedAccommodationProvider}.
 */
final class GetPublishedAccommodationCollectionBoundsTest extends AccommodationApiTestCase
{
    public function test_should_only_return_accommodations_inside_the_map_bounds(): void
    {
        // Paris (inside), Marseille (outside), and one without coordinates.
        $this->insertGeoAccommodation('Inside Paris', latitude: 48.85, longitude: 2.35);
        $this->insertGeoAccommodation('Outside Marseille', latitude: 43.30, longitude: 5.37);
        $this->insertGeoAccommodation('No coordinates', latitude: null, longitude: null);

        // A box tightly around Paris.
        $response = self::createClient()->request(
            'GET',
            '/api/accommodations?south=48.80&north=48.90&west=2.25&east=2.45',
        );

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Inside Paris', $members[0]['title']);
    }

    public function test_should_ignore_the_filter_when_bounds_are_incomplete(): void
    {
        $this->insertGeoAccommodation('Paris', latitude: 48.85, longitude: 2.35);
        $this->insertGeoAccommodation('Marseille', latitude: 43.30, longitude: 5.37);

        // Missing "east" → the whole box is ignored, every published listing is returned.
        $response = self::createClient()->request(
            'GET',
            '/api/accommodations?south=48.80&north=48.90&west=2.25',
        );

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    private function insertGeoAccommodation(string $title, ?float $latitude, ?float $longitude): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription('Description of '.$title)
            ->setPrice(100.0)
            ->setStatus('published')
            ->setCity('City')
            ->setCountry('France')
            ->setLatitude($latitude)
            ->setLongitude($longitude)
            ->setMaxGuests(2);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }
}
