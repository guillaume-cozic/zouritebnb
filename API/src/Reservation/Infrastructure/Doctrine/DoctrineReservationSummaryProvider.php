<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Doctrine;

use App\Shared\Domain\Port\ReservationSummary;
use App\Shared\Domain\Port\ReservationSummaryProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineReservationSummaryProvider implements ReservationSummaryProvider
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findById(Uuid $reservationId): ?ReservationSummary
    {
        $entity = $this->entityManager->find(ReservationEntity::class, $reservationId);

        if (null === $entity) {
            return null;
        }

        return new ReservationSummary(
            reservationId: $entity->getId(),
            accommodationId: $entity->getAccommodationId(),
            teamId: $entity->getTeamId(),
            guestUserId: $entity->getGuestUserId(),
            guestName: $entity->getGuestName(),
            checkIn: $entity->getCheckIn(),
            checkOut: $entity->getCheckOut(),
        );
    }
}
