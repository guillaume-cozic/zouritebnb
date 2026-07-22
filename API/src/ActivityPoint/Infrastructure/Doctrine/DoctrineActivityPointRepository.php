<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\Doctrine;

use App\ActivityPoint\Domain\Entity\ActivityPoint;
use App\ActivityPoint\Domain\Entity\ActivityPointCategory;
use App\ActivityPoint\Domain\Entity\Coordinates;
use App\ActivityPoint\Domain\Port\ActivityPointRepository as ActivityPointRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ActivityPointEntity>
 */
class DoctrineActivityPointRepository extends ServiceEntityRepository implements ActivityPointRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityPointEntity::class);
    }

    public function save(ActivityPoint $point): void
    {
        $entity = $this->find($point->getId()) ?? new ActivityPointEntity();
        $entity->setId($point->getId())
            ->setName($point->getName())
            ->setDescription($point->getDescription())
            ->setCategory($point->getCategory()->value)
            ->setLatitude($point->getCoordinates()->latitude())
            ->setLongitude($point->getCoordinates()->longitude())
            ->setArticleUrl($point->getArticleUrl());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?ActivityPoint
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function remove(Uuid $id): void
    {
        $entity = $this->find($id);
        if (null === $entity) {
            return;
        }

        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
    }

    private function toDomain(ActivityPointEntity $entity): ActivityPoint
    {
        return new ActivityPoint(
            id: $entity->getId(),
            name: $entity->getName(),
            description: $entity->getDescription(),
            category: ActivityPointCategory::from($entity->getCategory()),
            coordinates: new Coordinates($entity->getLatitude(), $entity->getLongitude()),
            articleUrl: $entity->getArticleUrl(),
        );
    }
}
