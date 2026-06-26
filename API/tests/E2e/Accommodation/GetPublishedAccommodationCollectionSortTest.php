<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Review\Infrastructure\Doctrine\ReviewEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Exercises the `sort` branch of
 * {@see \App\Accommodation\Infrastructure\ApiPlatform\PublishedAccommodationProvider}.
 */
final class GetPublishedAccommodationCollectionSortTest extends AccommodationApiTestCase
{
    public function test_should_sort_by_price_ascending(): void
    {
        $this->insertPublished('Mid', price: 150.0);
        $this->insertPublished('Cheap', price: 50.0);
        $this->insertPublished('Pricey', price: 400.0);

        $response = self::createClient()->request('GET', '/api/accommodations?sort=price_asc');

        self::assertResponseIsSuccessful();
        self::assertSame(['Cheap', 'Mid', 'Pricey'], array_column($response->toArray()['member'], 'title'));
    }

    public function test_should_sort_by_price_descending(): void
    {
        $this->insertPublished('Mid', price: 150.0);
        $this->insertPublished('Cheap', price: 50.0);
        $this->insertPublished('Pricey', price: 400.0);

        $response = self::createClient()->request('GET', '/api/accommodations?sort=price_desc');

        self::assertResponseIsSuccessful();
        self::assertSame(['Pricey', 'Mid', 'Cheap'], array_column($response->toArray()['member'], 'title'));
    }

    public function test_should_sort_by_rating_descending_then_unrated_last(): void
    {
        $low = $this->insertPublished('Low Rated', price: 100.0);
        $high = $this->insertPublished('High Rated', price: 100.0);
        $this->insertPublished('Unrated', price: 100.0);
        $this->insertReview($high, 5);
        $this->insertReview($high, 5);
        $this->insertReview($low, 2);

        $response = self::createClient()->request('GET', '/api/accommodations?sort=rating');

        self::assertResponseIsSuccessful();
        self::assertSame(['High Rated', 'Low Rated', 'Unrated'], array_column($response->toArray()['member'], 'title'));
    }

    public function test_should_default_to_alphabetical_order_for_unknown_sort(): void
    {
        $this->insertPublished('Banana', price: 100.0);
        $this->insertPublished('Apple', price: 100.0);

        $response = self::createClient()->request('GET', '/api/accommodations?sort=bogus');

        self::assertResponseIsSuccessful();
        self::assertSame(['Apple', 'Banana'], array_column($response->toArray()['member'], 'title'));
    }

    private function insertPublished(string $title, float $price): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription('Description of '.$title)
            ->setPrice($price)
            ->setStatus('published')
            ->setCity('Paris')
            ->setCountry('France')
            ->setMaxGuests(2);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }

    private function insertReview(string $accommodationId, int $rating): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new ReviewEntity()
            ->setId(Uuid::v7())
            ->setType('accommodation')
            ->setReservationId(Uuid::v7())
            ->setAuthorUserId(Uuid::v7())
            ->setSubjectAccommodationId(Uuid::fromString($accommodationId))
            ->setRating($rating)
            ->setComment('Comment')
            ->setCreatedAt(new \DateTimeImmutable('2026-01-01'));

        $em->persist($entity);
        $em->flush();
    }
}
