<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Exercises the availability window (`checkIn` / `checkOut`) filtering branch of
 * {@see \App\Accommodation\Infrastructure\ApiPlatform\PublishedAccommodationProvider}.
 */
final class GetPublishedAccommodationCollectionDateFilterTest extends AccommodationApiTestCase
{
    public function test_should_exclude_accommodations_booked_during_the_requested_range(): void
    {
        $this->insertPublished('Free Villa');
        $booked = $this->insertPublished('Booked Villa');
        $this->insertReservation($booked, '2026-08-12 15:00', '2026-08-14 11:00', 'confirmed');

        $response = self::createClient()->request('GET', '/api/accommodations?checkIn=2026-08-10&checkOut=2026-08-15');

        self::assertResponseIsSuccessful();
        self::assertSame(['Free Villa'], array_column($response->toArray()['member'], 'title'));
    }

    public function test_should_keep_accommodations_available_on_same_day_turnover(): void
    {
        $turnover = $this->insertPublished('Turnover Villa');
        // Existing stay leaves on the requested check-in day...
        $this->insertReservation($turnover, '2026-08-05 15:00', '2026-08-10 11:00', 'confirmed');
        // ...and another arrives on the requested check-out day.
        $this->insertReservation($turnover, '2026-08-15 15:00', '2026-08-20 11:00', 'confirmed');

        $response = self::createClient()->request('GET', '/api/accommodations?checkIn=2026-08-10&checkOut=2026-08-15');

        self::assertResponseIsSuccessful();
        self::assertSame(['Turnover Villa'], array_column($response->toArray()['member'], 'title'));
    }

    public function test_should_ignore_cancelled_and_refused_reservations(): void
    {
        $accommodation = $this->insertPublished('Was Cancelled');
        $this->insertReservation($accommodation, '2026-08-11 15:00', '2026-08-13 11:00', 'cancelled');
        $this->insertReservation($accommodation, '2026-08-11 15:00', '2026-08-13 11:00', 'refused');

        $response = self::createClient()->request('GET', '/api/accommodations?checkIn=2026-08-10&checkOut=2026-08-15');

        self::assertResponseIsSuccessful();
        self::assertSame(['Was Cancelled'], array_column($response->toArray()['member'], 'title'));
    }

    public function test_should_ignore_filter_when_only_one_date_is_provided(): void
    {
        $accommodation = $this->insertPublished('Half Range');
        $this->insertReservation($accommodation, '2026-08-12 15:00', '2026-08-14 11:00', 'confirmed');

        $response = self::createClient()->request('GET', '/api/accommodations?checkIn=2026-08-10');

        self::assertResponseIsSuccessful();
        self::assertSame(['Half Range'], array_column($response->toArray()['member'], 'title'));
    }

    public function test_should_ignore_filter_when_check_out_is_not_after_check_in(): void
    {
        $accommodation = $this->insertPublished('Inverted Range');
        $this->insertReservation($accommodation, '2026-08-12 15:00', '2026-08-14 11:00', 'confirmed');

        $response = self::createClient()->request('GET', '/api/accommodations?checkIn=2026-08-15&checkOut=2026-08-10');

        self::assertResponseIsSuccessful();
        self::assertSame(['Inverted Range'], array_column($response->toArray()['member'], 'title'));
    }

    public function test_should_ignore_malformed_dates(): void
    {
        $accommodation = $this->insertPublished('Bad Dates');
        $this->insertReservation($accommodation, '2026-08-12 15:00', '2026-08-14 11:00', 'confirmed');

        $response = self::createClient()->request('GET', '/api/accommodations?checkIn=not-a-date&checkOut=2026-13-40');

        self::assertResponseIsSuccessful();
        self::assertSame(['Bad Dates'], array_column($response->toArray()['member'], 'title'));
    }

    private function insertPublished(string $title): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription('Description of '.$title)
            ->setPrice(100.0)
            ->setStatus('published')
            ->setCity('Paris')
            ->setCountry('France')
            ->setMaxGuests(2);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }

    private function insertReservation(string $accommodationId, string $checkIn, string $checkOut, string $status): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new ReservationEntity()
            ->setId(Uuid::v7())
            ->setAccommodationId(Uuid::fromString($accommodationId))
            ->setTeamId(Uuid::fromString(self::OWNER_TEAM_ID))
            ->setCheckIn(new \DateTimeImmutable($checkIn))
            ->setCheckOut(new \DateTimeImmutable($checkOut))
            ->setGuestName('Guest')
            ->setGuestCount(1)
            ->setStatus($status);

        $em->persist($entity);
        $em->flush();
    }
}
