<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Doctrine;

use App\Reservation\Domain\Entity\CancellationPolicy;
use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestCount;
use App\Reservation\Domain\Entity\GuestName;
use App\Reservation\Domain\Entity\PendingModification;
use App\Reservation\Domain\Entity\Reservation as DomainReservation;
use App\Reservation\Domain\Entity\ReservationId;
use App\Reservation\Domain\Entity\ReservationPrice;
use App\Reservation\Domain\Entity\ReservationStatus;
use App\Reservation\Domain\Port\ReservationRepository as ReservationRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ReservationEntity>
 */
class DoctrineReservationRepository extends ServiceEntityRepository implements ReservationRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationEntity::class);
    }

    public function save(DomainReservation $reservation): void
    {
        $id = $reservation->getId()->toUuid();
        $entity = $this->find($id) ?? new ReservationEntity();

        $entity
            ->setId($id)
            ->setAccommodationId($reservation->getAccommodationId())
            ->setTeamId($reservation->getTeamId())
            ->setGuestUserId($reservation->getGuestUserId())
            ->setCheckIn($reservation->getDateRange()->checkIn())
            ->setCheckOut($reservation->getDateRange()->checkOut())
            ->setGuestName($reservation->getGuestName()->toString())
            ->setGuestCount($reservation->getGuestCount()->value())
            ->setStatus($reservation->getStatus()->value)
            ->setTotalPrice($reservation->getPrice()->totalPrice)
            ->setPricePerNight($reservation->getPrice()->pricePerNight)
            ->setAppliedDiscountPercentage($reservation->getPrice()->appliedDiscountPercentage)
            ->setCommissionAmount($reservation->getPrice()->commissionAmount)
            ->setDonationAmount($reservation->getPrice()->donationAmount)
            ->setExtraServicesTotal($reservation->getPrice()->extraServicesTotal)
            ->setCancellationPolicy($reservation->getCancellationPolicy()->value)
            ->setCancelledByHost($reservation->isCancelledByHost())
            ->setPendingModification($reservation->getPendingModification()?->toArray());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function ofId(ReservationId $id): ?DomainReservation
    {
        $entity = $this->find($id->toUuid());

        return $entity ? $this->toDomain($entity) : null;
    }

    public function list(
        Uuid $teamId,
        Uuid $guestUserId,
        ?Uuid $accommodationId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.teamId = :teamId OR r.guestUserId = :guestUserId')
            ->setParameter('teamId', $teamId, \Symfony\Bridge\Doctrine\Types\UuidType::NAME)
            ->setParameter('guestUserId', $guestUserId, \Symfony\Bridge\Doctrine\Types\UuidType::NAME);

        if (null !== $accommodationId) {
            $qb->andWhere('r.accommodationId = :accommodationId')
                ->setParameter('accommodationId', $accommodationId, \Symfony\Bridge\Doctrine\Types\UuidType::NAME);
        }

        if (null !== $from) {
            $qb->andWhere('r.checkOut > :from')->setParameter('from', $from);
        }

        if (null !== $to) {
            $qb->andWhere('r.checkIn < :to')->setParameter('to', $to);
        }

        $entities = $qb->getQuery()->getResult();

        return array_map($this->toDomain(...), $entities);
    }

    public function busyRanges(Uuid $accommodationId, \DateTimeImmutable $from): array
    {
        $entities = $this->createQueryBuilder('r')
            ->andWhere('r.accommodationId = :accommodationId')
            ->andWhere('r.status IN (:statuses)')
            ->andWhere('r.checkOut > :from')
            ->setParameter('accommodationId', $accommodationId, \Symfony\Bridge\Doctrine\Types\UuidType::NAME)
            ->setParameter('statuses', [ReservationStatus::Pending->value, ReservationStatus::Confirmed->value])
            ->setParameter('from', $from)
            ->orderBy('r.checkIn', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (ReservationEntity $entity): DateRange => new DateRange(
                checkIn: $entity->getCheckIn(),
                checkOut: $entity->getCheckOut(),
            ),
            $entities,
        );
    }

    public function hasOverlappingReservation(Uuid $accommodationId, DateRange $dateRange, ?ReservationId $excludeReservationId = null): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.accommodationId = :accommodationId')
            ->andWhere('r.status IN (:statuses)')
            // Strict bounds: an existing stay leaving on the requested check-in day
            // (or arriving on the requested check-out day) does not overlap.
            ->andWhere('r.checkIn < :checkOut')
            ->andWhere('r.checkOut > :checkIn')
            ->setParameter('accommodationId', $accommodationId, \Symfony\Bridge\Doctrine\Types\UuidType::NAME)
            ->setParameter('statuses', [ReservationStatus::Pending->value, ReservationStatus::Confirmed->value])
            ->setParameter('checkIn', $dateRange->checkIn())
            ->setParameter('checkOut', $dateRange->checkOut());

        if (null !== $excludeReservationId) {
            $qb->andWhere('r.id != :excludeId')
                ->setParameter('excludeId', $excludeReservationId->toUuid(), \Symfony\Bridge\Doctrine\Types\UuidType::NAME);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    private function toDomain(ReservationEntity $entity): DomainReservation
    {
        return new DomainReservation(
            id: new ReservationId($entity->getId()),
            accommodationId: $entity->getAccommodationId(),
            teamId: $entity->getTeamId(),
            dateRange: new DateRange(
                checkIn: $entity->getCheckIn(),
                checkOut: $entity->getCheckOut(),
            ),
            guestName: new GuestName($entity->getGuestName()),
            guestCount: new GuestCount($entity->getGuestCount()),
            status: ReservationStatus::from($entity->getStatus()),
            price: new ReservationPrice(
                totalPrice: $entity->getTotalPrice(),
                pricePerNight: $entity->getPricePerNight(),
                appliedDiscountPercentage: $entity->getAppliedDiscountPercentage(),
                commissionAmount: $entity->getCommissionAmount(),
                donationAmount: $entity->getDonationAmount(),
                extraServicesTotal: $entity->getExtraServicesTotal(),
            ),
            guestUserId: $entity->getGuestUserId(),
            cancellationPolicy: CancellationPolicy::fromString($entity->getCancellationPolicy()),
            cancelledByHost: $entity->isCancelledByHost(),
            pendingModification: null === $entity->getPendingModification()
                ? null
                : PendingModification::fromArray($entity->getPendingModification()),
        );
    }
}
