<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class CreateReservationTest extends ReservationApiTestCase
{
    private function insertAccommodation(float $pricePerNight = 100.0, ?float $weeklyPromotionPercentage = null): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();
        $entity = new AccommodationEntity()
            ->setId($id)
            ->setTitle('Test')
            ->setDescription('Test description')
            ->setPrice($pricePerNight)
            ->setStatus('draft')
            ->setWeeklyPromotionPercentage($weeklyPromotionPercentage);

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    public function testShouldCreateReservation(): void
    {
        $accommodationId = $this->insertAccommodation(100.0);

        self::createClient()->request('POST', '/api/reservations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-05-01T15:00:00+00:00',
                'checkOut' => '2026-05-05T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'guestName' => 'Jean Dupont',
            'status' => 'confirmed',
            'totalPrice' => 400,
            'pricePerNight' => 100,
        ]);
    }

    public function testShouldApplyWeeklyPromotionForStayOfSevenNightsOrMore(): void
    {
        $accommodationId = $this->insertAccommodation(100.0, 20.0);

        self::createClient()->request('POST', '/api/reservations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-05-01T15:00:00+00:00',
                'checkOut' => '2026-05-08T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'totalPrice' => 560,
            'pricePerNight' => 100,
            'appliedDiscountPercentage' => 20,
        ]);
    }

    public function testShouldReturn422WhenCheckOutIsBeforeCheckIn(): void
    {
        $accommodationId = $this->insertAccommodation();

        self::createClient()->request('POST', '/api/reservations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-05-10T15:00:00+00:00',
                'checkOut' => '2026-05-01T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testShouldReturn422WhenGuestNameIsEmpty(): void
    {
        $accommodationId = $this->insertAccommodation();

        self::createClient()->request('POST', '/api/reservations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-05-01T15:00:00+00:00',
                'checkOut' => '2026-05-05T11:00:00+00:00',
                'guestName' => '',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testShouldReturn422WhenAccommodationDoesNotExist(): void
    {
        self::createClient()->request('POST', '/api/reservations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => Uuid::v7()->toRfc4122(),
                'checkIn' => '2026-05-01T15:00:00+00:00',
                'checkOut' => '2026-05-05T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
