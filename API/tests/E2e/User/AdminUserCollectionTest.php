<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class AdminUserCollectionTest extends UserApiTestCase
{
    private const string HOST_TEAM_UUID = '00000000-0000-4000-8000-000000000042';

    public function test_should_list_all_users_as_admin(): void
    {
        $this->createAuthUser(email: 'admin@example.com', roles: ['ROLE_ADMIN']);
        $hostId = $this->createAuthUser(
            email: 'host@example.com',
            teamId: self::HOST_TEAM_UUID,
            firstName: 'Marie',
            lastName: 'Dupont',
        );

        $this->insertAccommodation(self::HOST_TEAM_UUID);
        $this->insertAccommodation(self::HOST_TEAM_UUID);
        $this->insertReservation($hostId);

        $response = self::createClient()->request('GET', '/api/admin/users', [
            'headers' => $this->authHeaders('admin@example.com'),
        ]);

        self::assertResponseIsSuccessful();

        $members = $response->toArray()['member'];
        self::assertCount(2, $members);

        self::assertJsonContains([
            'member' => [
                [
                    'email' => 'admin@example.com',
                    'firstName' => null,
                    'lastName' => null,
                    'roles' => ['ROLE_ADMIN'],
                    'verificationStatus' => 'not_started',
                    'accommodationCount' => 0,
                    'reservationCount' => 0,
                ],
                [
                    'id' => $hostId,
                    'email' => 'host@example.com',
                    'firstName' => 'Marie',
                    'lastName' => 'Dupont',
                    'roles' => [],
                    'verificationStatus' => 'not_started',
                    'teamId' => self::HOST_TEAM_UUID,
                    'accommodationCount' => 2,
                    'reservationCount' => 1,
                ],
            ],
        ]);

        foreach ($members as $member) {
            self::assertArrayNotHasKey('hashedPassword', $member);
            self::assertArrayNotHasKey('password', $member);
        }
    }

    public function test_should_return_403_when_not_admin(): void
    {
        $this->createAuthUser(email: 'host@example.com');

        self::createClient()->request('GET', '/api/admin/users', [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return_401_when_not_authenticated(): void
    {
        self::createClient()->request('GET', '/api/admin/users');

        self::assertResponseStatusCodeSame(401);
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        return $em;
    }

    private function insertAccommodation(string $teamId): string
    {
        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle('Villa du lagon')
            ->setDescription('Une description suffisante pour la fixture.')
            ->setPrice(100.0)
            ->setStatus('published')
            ->setTeamId(Uuid::fromString($teamId));

        $em = $this->entityManager();
        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }

    private function insertReservation(string $guestUserId): string
    {
        $id = Uuid::v7();
        $entity = new ReservationEntity()
            ->setId($id)
            ->setAccommodationId(Uuid::v7())
            ->setTeamId(Uuid::v7())
            ->setGuestUserId(Uuid::fromString($guestUserId))
            ->setCheckIn(new \DateTimeImmutable('2026-05-01T15:00:00+00:00'))
            ->setCheckOut(new \DateTimeImmutable('2026-05-05T11:00:00+00:00'))
            ->setGuestName('Marie Dupont')
            ->setStatus('confirmed')
            ->setTotalPrice(400.0)
            ->setPricePerNight(100.0)
            ->setAppliedDiscountPercentage(null);

        $em = $this->entityManager();
        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }
}
