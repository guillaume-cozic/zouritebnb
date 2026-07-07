<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Exercises the full-text keyword filter (?q=) of
 * {@see \App\Accommodation\Infrastructure\ApiPlatform\PublishedAccommodationProvider}.
 */
final class GetPublishedAccommodationCollectionKeywordTest extends AccommodationApiTestCase
{
    public function test_should_match_keyword_in_title(): void
    {
        $this->insertAccommodationWithText('Villa avec piscine', 'Grande maison familiale');
        $this->insertAccommodationWithText('Studio en ville', 'Proche du centre');

        $response = self::createClient()->request('GET', '/api/accommodations?q=piscine');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Villa avec piscine', $members[0]['title']);
    }

    public function test_should_match_keyword_in_description(): void
    {
        $this->insertAccommodationWithText('Villa du lagon', 'Superbe vue sur le lagon avec kitesurf');
        $this->insertAccommodationWithText('Studio en ville', 'Proche du centre');

        $response = self::createClient()->request('GET', '/api/accommodations?q=kitesurf');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Villa du lagon', $members[0]['title']);
    }

    public function test_should_be_case_insensitive(): void
    {
        $this->insertAccommodationWithText('Villa avec PISCINE', 'Grande maison');
        $this->insertAccommodationWithText('Studio en ville', 'Proche du centre');

        $response = self::createClient()->request('GET', '/api/accommodations?q=Piscine');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $response->toArray()['member']);
    }

    public function test_should_require_every_word_to_match_across_title_and_description(): void
    {
        // "villa" in the title, "piscine" in the description → matches.
        $this->insertAccommodationWithText('Villa du soleil', 'Avec piscine chauffée');
        // Only "villa" matches, not "piscine".
        $this->insertAccommodationWithText('Villa des sables', 'Face à la mer');

        $response = self::createClient()->request('GET', '/api/accommodations?q=villa piscine');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Villa du soleil', $members[0]['title']);
    }

    public function test_should_not_return_unpublished_accommodations(): void
    {
        $this->insertAccommodationWithText('Villa avec piscine', 'Grande maison', status: 'draft');

        $response = self::createClient()->request('GET', '/api/accommodations?q=piscine');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $response->toArray()['member']);
    }

    public function test_should_ignore_a_blank_keyword(): void
    {
        $this->insertAccommodationWithText('Villa avec piscine', 'Grande maison');
        $this->insertAccommodationWithText('Studio en ville', 'Proche du centre');

        $response = self::createClient()->request('GET', '/api/accommodations?q=%20%20');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_combine_with_other_filters(): void
    {
        $this->insertAccommodationWithText('Villa avec piscine à Paris', 'Grande maison', city: 'Paris');
        $this->insertAccommodationWithText('Villa avec piscine à Lyon', 'Grande maison', city: 'Lyon');

        $response = self::createClient()->request('GET', '/api/accommodations?q=piscine&city=Paris');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Villa avec piscine à Paris', $members[0]['title']);
    }

    private function insertAccommodationWithText(
        string $title,
        string $description,
        string $status = 'published',
        string $city = 'City',
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription($description)
            ->setPrice(100.0)
            ->setStatus($status)
            ->setCity($city)
            ->setCountry('France')
            ->setMaxGuests(2);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }
}
