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

    public function test_should_create_reservation(): void
    {
        $accommodationId = $this->insertAccommodation(100.0);

        self::createClient()->request('POST', '/api/reservations', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/ld+json'],
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
            'teamId' => self::DEFAULT_TEAM_UUID,
        ]);
    }

    public function test_should_return401_when_not_authenticated(): void
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

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_apply_weekly_promotion_for_stay_of_seven_nights_or_more(): void
    {
        $accommodationId = $this->insertAccommodation(100.0, 20.0);

        self::createClient()->request('POST', '/api/reservations', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/ld+json'],
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

    public function test_should_return422_when_check_out_is_before_check_in(): void
    {
        $accommodationId = $this->insertAccommodation();

        self::createClient()->request('POST', '/api/reservations', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-05-10T15:00:00+00:00',
                'checkOut' => '2026-05-01T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_guest_name_is_empty(): void
    {
        $accommodationId = $this->insertAccommodation();

        self::createClient()->request('POST', '/api/reservations', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-05-01T15:00:00+00:00',
                'checkOut' => '2026-05-05T11:00:00+00:00',
                'guestName' => '',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_accommodation_does_not_exist(): void
    {
        self::createClient()->request('POST', '/api/reservations', [
            'headers' => $this->hostAuthHeaders() + ['Content-Type' => 'application/ld+json'],
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
