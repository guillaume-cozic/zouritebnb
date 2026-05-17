<?php

declare(strict_types=1);

namespace App\SolidarityProject\Infrastructure\Doctrine;

use App\SolidarityProject\Domain\Entity\SolidarityProject;
use App\SolidarityProject\Domain\Port\SolidarityProjectRepository as SolidarityProjectRepositoryPort;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<SolidarityProjectEntity>
 */
class DoctrineSolidarityProjectRepository extends ServiceEntityRepository implements SolidarityProjectRepositoryPort
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolidarityProjectEntity::class);
    }

    public function save(SolidarityProject $project): void
    {
        $entity = $this->find($project->getId()) ?? new SolidarityProjectEntity();
        $entity->setId($project->getId())
            ->setTitle($project->getTitle())
            ->setDescription($project->getDescription())
            ->setImageUrl($project->getImageUrl())
            ->setStatus($project->getStatus())
            ->setCreatedAt($project->getCreatedAt())
            ->setIsDefault($project->isDefault());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function markAsDefault(Uuid $id): void
    {
        $em = $this->getEntityManager();
        $em->createQuery('UPDATE '.SolidarityProjectEntity::class.' p SET p.isDefault = false WHERE p.isDefault = true')
            ->execute();
        $em->createQuery('UPDATE '.SolidarityProjectEntity::class.' p SET p.isDefault = true WHERE p.id = :id')
            ->setParameter('id', $id, UuidType::NAME)
            ->execute();
        $em->clear();
    }

    public function findById(Uuid $id): ?SolidarityProject
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    /**
     * @return SolidarityProject[]
     */
    public function findAllActive(): array
    {
        $entities = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', SolidarityProject::STATUS_ACTIVE)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map($this->toDomain(...), $entities);
    }

    private function toDomain(SolidarityProjectEntity $entity): SolidarityProject
    {
        return new SolidarityProject(
            id: $entity->getId(),
            title: $entity->getTitle(),
            description: $entity->getDescription(),
            imageUrl: $entity->getImageUrl(),
            status: $entity->getStatus(),
            createdAt: $entity->getCreatedAt(),
            isDefault: $entity->isDefault(),
        );
    }
}
