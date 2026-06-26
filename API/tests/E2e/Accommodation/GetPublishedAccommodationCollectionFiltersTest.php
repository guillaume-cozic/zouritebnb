<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Exercises the filtering / pagination / sorting branches of
 * {@see \App\Accommodation\Infrastructure\ApiPlatform\PublishedAccommodationProvider}.
 */
final class GetPublishedAccommodationCollectionFiltersTest extends AccommodationApiTestCase
{
    public function test_should_filter_by_status_all_returns_draft_and_published(): void
    {
        $this->insertRichAccommodation('Draft One', status: 'draft');
        $this->insertRichAccommodation('Published One', status: 'published');

        $response = self::createClient()->request('GET', '/api/accommodations?status=all');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_filter_by_status_draft(): void
    {
        $this->insertRichAccommodation('Draft Only', status: 'draft');
        $this->insertRichAccommodation('Published Hidden', status: 'published');

        $response = self::createClient()->request('GET', '/api/accommodations?status=draft');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Draft Only', $members[0]['title']);
    }

    public function test_should_fall_back_to_published_when_status_is_invalid(): void
    {
        $this->insertRichAccommodation('Draft Skipped', status: 'draft');
        $this->insertRichAccommodation('Published Kept', status: 'published');

        $response = self::createClient()->request('GET', '/api/accommodations?status=bogus');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Published Kept', $members[0]['title']);
    }

    public function test_should_filter_by_city_ignoring_case_and_dashes(): void
    {
        $this->insertRichAccommodation('In Paris', status: 'published', city: 'Paris');
        $this->insertRichAccommodation('In Aix', status: 'published', city: 'Aix-en-Provence');

        $response = self::createClient()->request('GET', '/api/accommodations?city=aix%20en%20provence');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('In Aix', $members[0]['title']);
    }

    public function test_should_ignore_blank_city_filter(): void
    {
        $this->insertRichAccommodation('City A', status: 'published', city: 'Lyon');
        $this->insertRichAccommodation('City B', status: 'published', city: 'Nice');

        $response = self::createClient()->request('GET', '/api/accommodations?city=%20%20');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_filter_by_guests_including_null_capacity(): void
    {
        $this->insertRichAccommodation('Sleeps Four', status: 'published', maxGuests: 4);
        $this->insertRichAccommodation('Sleeps Two', status: 'published', maxGuests: 2);
        $this->insertRichAccommodation('Unknown Capacity', status: 'published', maxGuests: null);

        $response = self::createClient()->request('GET', '/api/accommodations?guests=4');

        self::assertResponseIsSuccessful();
        $titles = array_column($response->toArray()['member'], 'title');
        sort($titles);
        self::assertSame(['Sleeps Four', 'Unknown Capacity'], $titles);
    }

    public function test_should_ignore_zero_guests_filter(): void
    {
        $this->insertRichAccommodation('Any A', status: 'published', maxGuests: 1);
        $this->insertRichAccommodation('Any B', status: 'published', maxGuests: 2);

        $response = self::createClient()->request('GET', '/api/accommodations?guests=0');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_filter_by_price_range(): void
    {
        $this->insertRichAccommodation('Cheap', status: 'published', price: 50.0);
        $this->insertRichAccommodation('Mid', status: 'published', price: 150.0);
        $this->insertRichAccommodation('Pricey', status: 'published', price: 400.0);

        $response = self::createClient()->request('GET', '/api/accommodations?priceMin=100&priceMax=300');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Mid', $members[0]['title']);
    }

    public function test_should_ignore_non_numeric_price_filters(): void
    {
        $this->insertRichAccommodation('Price A', status: 'published', price: 80.0);
        $this->insertRichAccommodation('Price B', status: 'published', price: 220.0);

        $response = self::createClient()->request('GET', '/api/accommodations?priceMin=abc&priceMax=xyz');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_filter_by_all_amenities_as_array(): void
    {
        $this->insertRichAccommodation('Full Set', status: 'published', amenities: ['wifi', 'pool', 'parking']);
        $this->insertRichAccommodation('Wifi Only', status: 'published', amenities: ['wifi']);

        $response = self::createClient()->request('GET', '/api/accommodations?amenities[]=wifi&amenities[]=pool');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Full Set', $members[0]['title']);
    }

    public function test_should_filter_by_all_amenities_as_comma_separated_string(): void
    {
        $this->insertRichAccommodation('Full Set', status: 'published', amenities: ['wifi', 'pool', 'parking']);
        $this->insertRichAccommodation('Wifi Only', status: 'published', amenities: ['wifi']);

        $response = self::createClient()->request('GET', '/api/accommodations?amenities=wifi,pool');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Full Set', $members[0]['title']);
    }

    public function test_should_filter_by_instant_booking(): void
    {
        $this->insertRichAccommodation('Instant One', status: 'published', instantBooking: true);
        $this->insertRichAccommodation('On Request', status: 'published', instantBooking: false);

        $response = self::createClient()->request('GET', '/api/accommodations?instantBooking=true');

        self::assertResponseIsSuccessful();
        $members = $response->toArray()['member'];
        self::assertCount(1, $members);
        self::assertSame('Instant One', $members[0]['title']);
        self::assertTrue($members[0]['instantBooking']);
    }

    public function test_should_ignore_instant_booking_filter_when_not_truthy(): void
    {
        $this->insertRichAccommodation('Instant One', status: 'published', instantBooking: true);
        $this->insertRichAccommodation('On Request', status: 'published', instantBooking: false);

        $response = self::createClient()->request('GET', '/api/accommodations?instantBooking=false');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
    }

    public function test_should_paginate_and_clamp_items_per_page(): void
    {
        for ($i = 1; $i <= 3; ++$i) {
            $this->insertRichAccommodation(\sprintf('Item %02d', $i), status: 'published');
        }

        // itemsPerPage above the cap (30) is clamped, below 1 is raised to 1.
        $page1 = self::createClient()->request('GET', '/api/accommodations?page=1&itemsPerPage=2');
        $page2 = self::createClient()->request('GET', '/api/accommodations?page=2&itemsPerPage=2');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $page1->toArray()['member']);
        self::assertCount(1, $page2->toArray()['member']);
        self::assertSame(3, $page1->toArray()['totalItems']);
    }

    public function test_should_clamp_items_per_page_to_minimum_of_one(): void
    {
        $this->insertRichAccommodation('Only A', status: 'published');
        $this->insertRichAccommodation('Only B', status: 'published');

        $response = self::createClient()->request('GET', '/api/accommodations?itemsPerPage=0');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $response->toArray()['member']);
        self::assertSame(2, $response->toArray()['totalItems']);
    }

    public function test_should_expose_photo_urls_and_thumbnail(): void
    {
        $id = $this->insertRichAccommodation('With Photos', status: 'published');
        $this->insertPhoto($id, 'first.jpg');
        $this->insertPhoto($id, 'second.jpg');

        $response = self::createClient()->request('GET', '/api/accommodations');

        self::assertResponseIsSuccessful();
        $member = $response->toArray()['member'][0];
        self::assertSame('/uploads/photos/first.jpg', $member['thumbnailUrl']);
        self::assertContains('/uploads/photos/first.jpg', $member['photoUrls']);
        self::assertContains('/uploads/photos/second.jpg', $member['photoUrls']);
    }

    /**
     * @param list<string>|null $amenities
     */
    private function insertRichAccommodation(
        string $title,
        string $status = 'published',
        float $price = 100.0,
        ?string $city = 'Paris',
        ?int $maxGuests = 2,
        ?array $amenities = null,
        bool $instantBooking = false,
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription('Description of '.$title)
            ->setPrice($price)
            ->setStatus($status)
            ->setCity($city)
            ->setCountry('France')
            ->setLatitude(48.85)
            ->setLongitude(2.35)
            ->setMaxGuests($maxGuests)
            ->setAmenities($amenities)
            ->setInstantBooking($instantBooking);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }
}
