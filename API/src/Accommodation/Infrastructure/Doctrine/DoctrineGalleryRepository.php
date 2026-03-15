<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Doctrine;

use App\Accommodation\Domain\Entity\Gallery;
use App\Accommodation\Domain\Port\GalleryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<GalleryEntity>
 */
class DoctrineGalleryRepository extends ServiceEntityRepository implements GalleryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GalleryEntity::class);
    }

    public function findByAccommodationId(Uuid $accommodationId): Gallery
    {
        $entity = $this->find($accommodationId);

        if (null === $entity) {
            return new Gallery(accommodationId: $accommodationId);
        }

        return $this->toDomain($entity);
    }

    public function save(Gallery $gallery): void
    {
        $entity = $this->find($gallery->getAccommodationId()) ?? new GalleryEntity();

        $entity
            ->setAccommodationId($gallery->getAccommodationId())
            ->setPhotoIds(array_map(
                static fn (Uuid $id) => $id->toRfc4122(),
                $gallery->photoIds(),
            ));

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    private function toDomain(GalleryEntity $entity): Gallery
    {
        return new Gallery(
            accommodationId: $entity->getAccommodationId(),
            photoIds: array_map(
                static fn (string $id) => Uuid::fromString($id),
                $entity->getPhotoIds(),
            ),
        );
    }
}
