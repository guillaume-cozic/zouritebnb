<?php

declare(strict_types=1);

namespace App\Geography\Infrastructure\Doctrine;

use App\Geography\Domain\Entity\Locality;
use App\Geography\Domain\Port\LocalityRepository as LocalityRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<LocalityEntity>
 */
class DoctrineLocalityRepository extends ServiceEntityRepository implements LocalityRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LocalityEntity::class);
    }

    public function save(Locality $locality): void
    {
        $entity = $this->find($locality->getId()) ?? new LocalityEntity();
        $entity->setId($locality->getId())
            ->setName($locality->getName())
            ->setRegionId($locality->getRegionId());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?Locality
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    /**
     * @return Locality[]
     */
    public function findAll(): array
    {
        $entities = $this->createQueryBuilder('l')
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map($this->toDomain(...), $entities);
    }

    /**
     * @return Locality[]
     */
    public function findByRegionId(Uuid $regionId): array
    {
        $entities = $this->createQueryBuilder('l')
            ->where('l.regionId = :regionId')
            ->setParameter('regionId', $regionId, UuidType::NAME)
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map($this->toDomain(...), $entities);
    }

    /**
     * @return Locality[]
     */
    public function findByRegionCode(string $regionCode): array
    {
        $entities = $this->getEntityManager()->createQueryBuilder()
            ->select('l')
            ->from(LocalityEntity::class, 'l')
            ->innerJoin(RegionEntity::class, 'r', 'WITH', 'l.regionId = r.id')
            ->where('r.code = :code')
            ->setParameter('code', $regionCode)
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map($this->toDomain(...), $entities);
    }

    private function toDomain(LocalityEntity $entity): Locality
    {
        return new Locality(
            id: $entity->getId(),
            name: $entity->getName(),
            regionId: $entity->getRegionId(),
        );
    }
}
