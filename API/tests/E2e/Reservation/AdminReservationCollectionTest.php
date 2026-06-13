<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class AdminReservationCollectionTest extends ReservationApiTestCase
{
    public function test_should_list_all_reservations_as_admin(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $guestUserId = $this->createAuthUser(email: 'guest@example.com');
        $accommodationId = $this->insertAccommodation('Villa du lagon');

        $latestId = $this->insertReservation(
            accommodationId: $accommodationId,
            checkIn: '2026-06-01T15:00:00+00:00',
            checkOut: '2026-06-05T11:00:00+00:00',
            guestName: 'Alice Martin',
            status: 'confirmed',
            guestUserId: $guestUserId,
        );
        $oldestId = $this->insertReservation(
            checkIn: '2026-05-01T15:00:00+00:00',
            checkOut: '2026-05-05T11:00:00+00:00',
            guestName: 'Bob Durand',
        );

        $response = self::createClient()->request('GET', '/api/admin/reservations', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        self::assertCount(2, $response->toArray()['member']);
        self::assertJsonContains([
            'member' => [
                [
                    'id' => $latestId,
                    'guestName' => 'Alice Martin',
                    'guestUserId' => $guestUserId,
                    'accommodationId' => $accommodationId,
                    'accommodationTitle' => 'Villa du lagon',
                    'teamId' => self::DEFAULT_TEAM_UUID,
                    'checkIn' => '2026-06-01T15:00:00+00:00',
                    'checkOut' => '2026-06-05T11:00:00+00:00',
                    'status' => 'confirmed',
                    'totalPrice' => 400,
                    'pricePerNight' => 100,
                    'appliedDiscountPercentage' => null,
                ],
                [
                    'id' => $oldestId,
                    'guestName' => 'Bob Durand',
                    'guestUserId' => null,
                    'accommodationTitle' => null,
                    'status' => 'pending',
                ],
            ],
        ]);
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');

        self::createClient()->request('GET', '/api/admin/reservations', [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        self::createClient()->request('GET', '/api/admin/reservations');

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_paginate_reservations(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        for ($i = 0; $i < 25; ++$i) {
            $this->insertReservation(guestName: \sprintf('Guest %02d', $i));
        }

        $response = self::createClient()->request('GET', '/api/admin/reservations?itemsPerPage=10&page=2', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertCount(10, $data['member']);
        self::assertSame(25, $data['totalItems']);
    }

    public function test_should_filter_reservations_by_search(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $this->insertReservation(guestName: 'Alice Martin', status: 'confirmed');
        $this->insertReservation(guestName: 'Bob Durand', status: 'pending');

        $response = self::createClient()->request('GET', '/api/admin/reservations?search=Alice', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertCount(1, $data['member']);
        self::assertSame('Alice Martin', $data['member'][0]['guestName']);
    }

    public function test_should_filter_reservations_by_status(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $this->insertReservation(guestName: 'Alice Martin', status: 'confirmed');
        $this->insertReservation(guestName: 'Bob Durand', status: 'pending');

        $response = self::createClient()->request('GET', '/api/admin/reservations?status=pending', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertCount(1, $data['member']);
        self::assertSame('Bob Durand', $data['member'][0]['guestName']);
    }

    private function insertAccommodation(string $title): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription('Une description suffisante pour la fixture.')
            ->setPrice(100.0)
            ->setStatus('published')
            ->setTeamId(Uuid::fromString(self::DEFAULT_TEAM_UUID));

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }
}
