<?php

declare(strict_types=1);

namespace App\Geography\Infrastructure\Doctrine;

use App\Geography\Domain\Entity\Region;
use App\Geography\Domain\Port\RegionRepository as RegionRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<RegionEntity>
 */
class DoctrineRegionRepository extends ServiceEntityRepository implements RegionRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegionEntity::class);
    }

    public function save(Region $region): void
    {
        $entity = $this->find($region->getId()) ?? new RegionEntity();
        $entity->setId($region->getId())
            ->setCode($region->getCode())
            ->setName($region->getName());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?Region
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function findByCode(string $code): ?Region
    {
        $entity = $this->findOneBy(['code' => $code]);

        return $entity ? $this->toDomain($entity) : null;
    }

    /**
     * @return Region[]
     */
    public function findAll(): array
    {
        $entities = $this->createQueryBuilder('r')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map($this->toDomain(...), $entities);
    }

    private function toDomain(RegionEntity $entity): Region
    {
        return new Region(
            id: $entity->getId(),
            code: $entity->getCode(),
            name: $entity->getName(),
        );
    }
}
