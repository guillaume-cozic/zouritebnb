<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Doctrine;

use App\Accommodation\Domain\Entity\Photo;
use App\Accommodation\Domain\Port\PhotoRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<PhotoEntity>
 */
class DoctrinePhotoRepository extends ServiceEntityRepository implements PhotoRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhotoEntity::class);
    }

    public function save(Photo $photo): void
    {
        $entity = $this->find($photo->getId()) ?? new PhotoEntity();

        $entity
            ->setId($photo->getId())
            ->setAccommodationId($photo->getAccommodationId())
            ->setFilename($photo->getFilename())
            ->setOriginalName($photo->getOriginalName())
            ->setMimeType($photo->getMimeType())
            ->setSize($photo->getSize());

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    public function findById(Uuid $id): ?Photo
    {
        $entity = $this->find($id);

        return $entity ? $this->toDomain($entity) : null;
    }

    public function delete(Photo $photo): void
    {
        $entity = $this->find($photo->getId());

        if (null !== $entity) {
            $em = $this->getEntityManager();
            $em->remove($entity);
            $em->flush();
        }
    }

    private function toDomain(PhotoEntity $entity): Photo
    {
        return new Photo(
            id: $entity->getId(),
            accommodationId: $entity->getAccommodationId(),
            filename: $entity->getFilename(),
            originalName: $entity->getOriginalName(),
            mimeType: $entity->getMimeType(),
            size: $entity->getSize(),
        );
    }
}
