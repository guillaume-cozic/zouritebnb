<?php

declare(strict_types=1);

namespace App\Review\Infrastructure\Doctrine;

use App\Review\Domain\Entity\Rating;
use App\Review\Domain\Entity\Review as DomainReview;
use App\Review\Domain\Entity\ReviewComment;
use App\Review\Domain\Entity\ReviewType;
use App\Review\Domain\Port\ReviewRepository as ReviewRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ReviewEntity>
 */
class DoctrineReviewRepository extends ServiceEntityRepository implements ReviewRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewEntity::class);
    }

    public function save(DomainReview $review): void
    {
        $id = $review->getId();
        $entity = $this->find($id) ?? new ReviewEntity();

        $entity
            ->setId($id)
            ->setType($review->getType()->value)
            ->setReservationId($review->getReservationId())
            ->setAuthorUserId($review->getAuthorUserId())
            ->setSubjectAccommodationId($review->getSubjectAccommodationId())
            ->setSubjectUserId($review->getSubjectUserId())
            ->setRating($review->getRating()->toInt())
            ->setComment($review->getComment()->toString())
            ->setCreatedAt($review->getCreatedAt());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?DomainReview
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function existsForAuthorAndStay(Uuid $authorUserId, Uuid $reservationId, ReviewType $type): bool
    {
        $count = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.authorUserId = :authorUserId')
            ->andWhere('r.reservationId = :reservationId')
            ->andWhere('r.type = :type')
            ->setParameter('authorUserId', $authorUserId, UuidType::NAME)
            ->setParameter('reservationId', $reservationId, UuidType::NAME)
            ->setParameter('type', $type->value)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    private function toDomain(ReviewEntity $entity): DomainReview
    {
        $type = ReviewType::from((string) $entity->getType());
        $rating = new Rating($entity->getRating());
        $comment = new ReviewComment($entity->getComment());

        return match ($type) {
            ReviewType::Accommodation => DomainReview::onAccommodation(
                id: $entity->getId(),
                reservationId: $entity->getReservationId(),
                authorUserId: $entity->getAuthorUserId(),
                subjectAccommodationId: $entity->getSubjectAccommodationId(),
                rating: $rating,
                comment: $comment,
                createdAt: $entity->getCreatedAt(),
            ),
            ReviewType::Guest => DomainReview::onGuest(
                id: $entity->getId(),
                reservationId: $entity->getReservationId(),
                authorUserId: $entity->getAuthorUserId(),
                subjectUserId: $entity->getSubjectUserId(),
                rating: $rating,
                comment: $comment,
                createdAt: $entity->getCreatedAt(),
            ),
        };
    }
}
