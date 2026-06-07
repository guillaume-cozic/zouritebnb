<?php

declare(strict_types=1);

namespace App\Tests\E2e\Accommodation;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Accommodation\Infrastructure\Doctrine\AccommodationEntity;
use App\Accommodation\Infrastructure\Doctrine\GalleryEntity;
use App\Accommodation\Infrastructure\Doctrine\PhotoEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

abstract class AccommodationApiTestCase extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected static ?bool $alwaysBootKernel = true;

    /**
     * Team UUID used by default for the authenticated host and the accommodations they own.
     */
    protected const OWNER_TEAM_ID = '019cf27a-96ba-7957-8622-eeccb7350e79';

    protected function insertAccommodation(string $title, string $description, float $price, string $status = 'draft', ?string $teamId = self::OWNER_TEAM_ID): string
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new AccommodationEntity()
            ->setId(Uuid::v7())
            ->setTitle($title)
            ->setDescription($description)
            ->setPrice($price)
            ->setStatus($status)
            ->setTeamId(null === $teamId ? null : Uuid::fromString($teamId));

        $em->persist($entity);
        $em->flush();

        return $entity->getId()->toRfc4122();
    }

    /**
     * Persists the authenticated host user (member of OWNER_TEAM_ID) and returns the
     * Authorization header to act as the owner of accommodations created via insertAccommodation().
     *
     * @return array{Authorization: string}
     */
    protected function authenticatedOwnerHeaders(string $email = 'owner@example.com'): array
    {
        $this->createAuthUser(email: $email, teamId: self::OWNER_TEAM_ID);

        return $this->authHeaders($email);
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
