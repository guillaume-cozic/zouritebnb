<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class RequestReservationTest extends ReservationApiTestCase
{
    private function insertAccommodation(?Uuid $teamId = null, float $pricePerNight = 100.0): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();
        $entity = new AccommodationEntity()
            ->setId($id)
            ->setTitle('Test')
            ->setDescription('Test description')
            ->setPrice($pricePerNight)
            ->setStatus('published')
            ->setTeamId($teamId ?? Uuid::fromString(self::DEFAULT_TEAM_UUID));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    public function test_should_request_reservation_as_pending(): void
    {
        $teamId = Uuid::fromString(self::DEFAULT_TEAM_UUID);
        $accommodationId = $this->insertAccommodation($teamId, 100.0);
        // The guest is the authenticated user (different team than the host).
        $guestUserId = $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('POST', '/api/reservations/request', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/ld+json'],
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
            'guestUserId' => $guestUserId,
            'status' => 'pending',
            'totalPrice' => 400,
            'pricePerNight' => 100,
            'teamId' => $teamId->toRfc4122(),
        ]);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $accommodationId = $this->insertAccommodation();

        self::createClient()->request('POST', '/api/reservations/request', [
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

    public function test_should_return422_when_accommodation_does_not_exist(): void
    {
        $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('POST', '/api/reservations/request', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => Uuid::v7()->toRfc4122(),
                'checkIn' => '2026-05-01T15:00:00+00:00',
                'checkOut' => '2026-05-05T11:00:00+00:00',
                'guestName' => 'Jean Dupont',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_check_out_before_check_in(): void
    {
        $accommodationId = $this->insertAccommodation();
        $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('POST', '/api/reservations/request', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/ld+json'],
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
        $this->createAuthUser(email: 'guest@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('POST', '/api/reservations/request', [
            'headers' => $this->authHeaders('guest@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId,
                'checkIn' => '2026-05-01T15:00:00+00:00',
                'checkOut' => '2026-05-05T11:00:00+00:00',
                'guestName' => '',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
