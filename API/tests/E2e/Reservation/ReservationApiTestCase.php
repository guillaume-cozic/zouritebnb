<?php

declare(strict_types=1);

namespace App\Tests\E2e\Reservation;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class ReservationApiTestCase extends ApiTestCase
{
    protected const string DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    protected static ?bool $alwaysBootKernel = true;

    protected function insertReservation(
        ?string $accommodationId = null,
        ?string $teamId = null,
        string $checkIn = '2026-05-01T15:00:00+00:00',
        string $checkOut = '2026-05-05T11:00:00+00:00',
        string $guestName = 'Jean Dupont',
        string $status = 'pending',
    ): string {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::v7();

        $entity = new ReservationEntity()
            ->setId($id)
            ->setAccommodationId(Uuid::fromString($accommodationId ?? Uuid::v7()->toRfc4122()))
            ->setTeamId(Uuid::fromString($teamId ?? self::DEFAULT_TEAM_UUID))
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

    protected function createReservationViaApi(
        ?string $accommodationId = null,
        string $checkIn = '2026-05-01T15:00:00+00:00',
        string $checkOut = '2026-05-05T11:00:00+00:00',
        string $guestName = 'Jean Dupont',
    ): string {
        $response = self::createClient()->request('POST', '/api/reservations', [
            'headers' => ['Content-Type' => 'application/ld+json'],
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
