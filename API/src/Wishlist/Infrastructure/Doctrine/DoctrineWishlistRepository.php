<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Doctrine;

use App\Wishlist\Domain\Entity\WishlistItem;
use App\Wishlist\Domain\Entity\WishlistOwner;
use App\Wishlist\Domain\Port\WishlistRepository as WishlistRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<WishlistItemEntity>
 */
class DoctrineWishlistRepository extends ServiceEntityRepository implements WishlistRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistItemEntity::class);
    }

    public function add(WishlistItem $item): void
    {
        $entity = new WishlistItemEntity()
            ->setId($item->id)
            ->setUserId($item->owner->userId)
            ->setCorrelationId($item->owner->correlationId)
            ->setAccommodationId($item->accommodationId)
            ->setCreatedAt(new \DateTimeImmutable());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function remove(WishlistOwner $owner, Uuid $accommodationId): void
    {
        $qb = $this->createQueryBuilder('w')
            ->delete()
            ->andWhere('w.accommodationId = :accommodationId')
            ->setParameter('accommodationId', $accommodationId, UuidType::NAME);

        $this->applyOwner($qb, $owner);

        $qb->getQuery()->execute();
    }

    public function exists(WishlistOwner $owner, Uuid $accommodationId): bool
    {
        $qb = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.accommodationId = :accommodationId')
            ->setParameter('accommodationId', $accommodationId, UuidType::NAME);

        $this->applyOwner($qb, $owner);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function transferOwnership(Uuid $correlationId, Uuid $userId): void
    {
        $connection = $this->getEntityManager()->getConnection();

        // Drop anonymous items the user already saved, to avoid violating the
        // (user_id, accommodation_id) uniqueness when re-attaching.
        $connection->executeStatement(
            <<<'SQL'
                DELETE anon FROM wishlist_item anon
                JOIN wishlist_item owned
                    ON owned.accommodation_id = anon.accommodation_id
                    AND owned.user_id = UUID_TO_BIN(:userId)
                WHERE anon.correlation_id = UUID_TO_BIN(:correlationId)
                SQL,
            ['userId' => $userId->toRfc4122(), 'correlationId' => $correlationId->toRfc4122()],
        );

        // Re-attach the remaining anonymous items to the user.
        $connection->executeStatement(
            <<<'SQL'
                UPDATE wishlist_item
                SET user_id = UUID_TO_BIN(:userId), correlation_id = NULL
                WHERE correlation_id = UUID_TO_BIN(:correlationId)
                SQL,
            ['userId' => $userId->toRfc4122(), 'correlationId' => $correlationId->toRfc4122()],
        );
    }

    private function applyOwner(\Doctrine\ORM\QueryBuilder $qb, WishlistOwner $owner): void
    {
        if ($owner->isUser()) {
            $qb->andWhere('w.userId = :userId')
                ->setParameter('userId', $owner->userId, UuidType::NAME);

            return;
        }

        $qb->andWhere('w.correlationId = :correlationId')
            ->setParameter('correlationId', $owner->correlationId, UuidType::NAME);
    }
}
