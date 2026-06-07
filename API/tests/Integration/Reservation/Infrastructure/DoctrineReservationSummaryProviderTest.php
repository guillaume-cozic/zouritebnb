<?php

declare(strict_types=1);

namespace App\Tests\Integration\Reservation\Infrastructure;

use App\Reservation\Infrastructure\Doctrine\ReservationEntity;
use App\Shared\Domain\Port\ReservationSummaryProvider;
use App\Tests\Integration\RepositoryTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineReservationSummaryProviderTest extends RepositoryTestCase
{
    private ReservationSummaryProvider $provider;
    private EntityManagerInterface $entityManager;

    #[Before]
    public function initProvider(): void
    {
        $this->provider = self::getContainer()->get(ReservationSummaryProvider::class);
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    public function test_should_return_summary_when_reservation_exists(): void
    {
        $id = Uuid::v7();
        $accommodationId = Uuid::v7();
        $teamId = Uuid::v7();
        $guestUserId = Uuid::v7();

        $entity = new ReservationEntity()
            ->setId($id)
            ->setAccommodationId($accommodationId)
            ->setTeamId($teamId)
            ->setGuestUserId($guestUserId)
            ->setCheckIn(new \DateTimeImmutable('2026-05-01T15:00:00+00:00'))
            ->setCheckOut(new \DateTimeImmutable('2026-05-05T11:00:00+00:00'))
            ->setGuestName('Alice Martin')
            ->setStatus('pending')
            ->setTotalPrice(400.0)
            ->setPricePerNight(100.0)
            ->setAppliedDiscountPercentage(null);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $summary = $this->provider->findById($id);

        self::assertNotNull($summary);
        self::assertSame($id->toRfc4122(), $summary->reservationId->toRfc4122());
        self::assertSame($accommodationId->toRfc4122(), $summary->accommodationId->toRfc4122());
        self::assertSame($teamId->toRfc4122(), $summary->teamId->toRfc4122());
        self::assertNotNull($summary->guestUserId);
        self::assertSame($guestUserId->toRfc4122(), $summary->guestUserId->toRfc4122());
        self::assertSame('Alice Martin', $summary->guestName);
        self::assertEquals(new \DateTimeImmutable('2026-05-01T15:00:00+00:00'), $summary->checkIn);
        self::assertEquals(new \DateTimeImmutable('2026-05-05T11:00:00+00:00'), $summary->checkOut);
    }

    public function test_should_return_null_when_reservation_not_found(): void
    {
        $summary = $this->provider->findById(Uuid::v7());

        self::assertNull($summary);
    }
}
