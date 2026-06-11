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
