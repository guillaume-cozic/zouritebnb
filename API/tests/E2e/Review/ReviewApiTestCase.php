<?php

declare(strict_types=1);

namespace App\Tests\E2e\Review;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
use App\User\Infrastructure\Doctrine\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class ReviewApiTestCase extends ApiTestCase
{
    protected const string DEFAULT_TEAM_UUID = '00000000-0000-4000-8000-000000000001';

    protected static ?bool $alwaysBootKernel = true;

    protected function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        return $em;
    }

    /**
     * Inserts a confirmed reservation whose checkout date is in the past, i.e. a completed stay.
     */
    protected function insertCompletedStay(
        string $accommodationId,
        string $guestUserId,
        ?string $teamId = null,
        string $checkIn = '2026-04-01T15:00:00+00:00',
        string $checkOut = '2026-04-05T11:00:00+00:00',
        string $status = 'confirmed',
    ): string {
        $em = $this->entityManager();

        $id = Uuid::v7();
        $entity = new ReservationEntity()
            ->setId($id)
            ->setAccommodationId(Uuid::fromString($accommodationId))
            ->setTeamId(Uuid::fromString($teamId ?? self::DEFAULT_TEAM_UUID))
            ->setGuestUserId(Uuid::fromString($guestUserId))
            ->setCheckIn(new \DateTimeImmutable($checkIn))
            ->setCheckOut(new \DateTimeImmutable($checkOut))
            ->setGuestName('Jean Dupont')
            ->setStatus($status)
            ->setTotalPrice(400.0)
            ->setPricePerNight(100.0)
            ->setAppliedDiscountPercentage(null);

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }

    protected function insertUser(?string $teamId = null): string
    {
        $em = $this->entityManager();

        $id = Uuid::v7();
        $entity = new UserEntity()
            ->setId($id)
            ->setEmail(\sprintf('user-%s@example.test', $id->toRfc4122()))
            ->setHashedPassword('$2y$13$dummyhashforfixtures')
            ->setTeamId(Uuid::fromString($teamId ?? self::DEFAULT_TEAM_UUID));

        $em->persist($entity);
        $em->flush();

        return $id->toRfc4122();
    }
}
