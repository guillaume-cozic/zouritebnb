<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class ReservationApiTestCase extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected const string DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    protected static ?bool $alwaysBootKernel = true;

    protected function insertReservation(
        ?string $accommodationId = null,
        ?string $teamId = null,
        string $checkIn = '2026-05-01T15:00:00+00:00',
        string $checkOut = '2026-05-05T11:00:00+00:00',
        string $guestName = 'Jean Dupont',
        string $status = 'pending',
        ?string $guestUserId = null,
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();

        $entity = new ReservationEntity()
            ->setId($id)
            ->setAccommodationId(Uuid::fromString($accommodationId ?? Uuid::v7()->toRfc4122()))
            ->setTeamId(Uuid::fromString($teamId ?? self::DEFAULT_TEAM_UUID))
            ->setGuestUserId(null === $guestUserId ? null : Uuid::fromString($guestUserId))
            ->setCheckIn(new \DateTimeImmutable($checkIn))
            ->setCheckOut(new \DateTimeImmutable($checkOut))
            ->setGuestName($guestName)
            ->setStatus($status)
            ->setTotalPrice(400.0)
            ->setPricePerNight(100.0)
            ->setAppliedDiscountPercentage(null);

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    /**
     * Creates an authenticated host user attached to the default host team and returns
     * the Authorization header array to use for host-scoped requests.
     *
     * @return array{Authorization: string}
     */
    protected function hostAuthHeaders(string $email = 'host@example.com'): array
    {
        $this->createAuthUser(email: $email, teamId: self::DEFAULT_TEAM_UUID);

        return $this->authHeaders($email);
    }

    protected function createReservationViaApi(
        ?string $accommodationId = null,
        string $checkIn = '2026-05-01T15:00:00+00:00',
        string $checkOut = '2026-05-05T11:00:00+00:00',
        string $guestName = 'Jean Dupont',
        ?array $authHeaders = null,
    ): string {
        $authHeaders ??= $this->hostAuthHeaders();

        $response = self::createClient()->request('POST', '/api/reservations', [
            'headers' => $authHeaders + ['Content-Type' => 'application/ld+json'],
            'json' => [
                'accommodationId' => $accommodationId ?? Uuid::v7()->toRfc4122(),
                'checkIn' => $checkIn,
                'checkOut' => $checkOut,
                'guestName' => $guestName,
            ],
        ]);

        return $response->toArray()['id'];
    }
}
