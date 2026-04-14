<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Doctrine;

use App\Reservation\Domain\Entity\DateRange;
use App\Reservation\Domain\Entity\GuestName;
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
            ->setCheckIn($reservation->getDateRange()->checkIn())
            ->setCheckOut($reservation->getDateRange()->checkOut())
            ->setGuestName($reservation->getGuestName()->toString())
            ->setStatus($reservation->getStatus()->value)
            ->setTotalPrice($reservation->getPrice()->totalPrice)
            ->setPricePerNight($reservation->getPrice()->pricePerNight)
            ->setAppliedDiscountPercentage($reservation->getPrice()->appliedDiscountPercentage);

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
        ?Uuid $accommodationId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.teamId = :teamId')
            ->setParameter('teamId', $teamId, \Symfony\Bridge\Doctrine\Types\UuidType::NAME);

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
            status: ReservationStatus::from($entity->getStatus()),
            price: new ReservationPrice(
                totalPrice: $entity->getTotalPrice(),
                pricePerNight: $entity->getPricePerNight(),
                appliedDiscountPercentage: $entity->getAppliedDiscountPercentage(),
            ),
        );
    }
}
