<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Accommodation\Infrastructure\Doctrine\GalleryEntity;
use App\Accommodation\Infrastructure\Doctrine\PhotoEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class AccommodationApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected function insertAccommodation(string $title, string $description, float $price, string $status = 'draft'): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription($description)
            ->setPrice($price)
            ->setStatus($status);

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }

    protected function insertPhoto(string $accommodationId, string $filename = 'photo.jpg', string $originalName = 'photo.jpg', string $mimeType = 'image/jpeg', int $size = 1024): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $photoId = Uuid::v7();

        $entity = new PhotoEntity();
        $entity->setId($photoId)
            ->setAccommodationId(Uuid::fromString($accommodationId))
            ->setFilename($filename)
            ->setOriginalName($originalName)
            ->setMimeType($mimeType)
            ->setSize($size);

        $em->persist($entity);

        $accommodationUuid = Uuid::fromString($accommodationId);
        $galleryEntity = $em->find(GalleryEntity::class, $accommodationUuid);

        if (null === $galleryEntity) {
            $galleryEntity = new GalleryEntity();
            $galleryEntity->setAccommodationId($accommodationUuid);
            $galleryEntity->setPhotoIds([$photoId->toRfc4122()]);
        } else {
            $photoIds = $galleryEntity->getPhotoIds();
            $photoIds[] = $photoId->toRfc4122();
            $galleryEntity->setPhotoIds($photoIds);
        }

        $em->persist($galleryEntity);
        $em->flush();

        return $photoId->toRfc4122();
    }
}
