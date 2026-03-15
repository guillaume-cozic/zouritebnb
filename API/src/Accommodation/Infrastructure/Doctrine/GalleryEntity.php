<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineGalleryRepository::class)]
#[ORM\Table(name: 'accommodation_gallery')]
class GalleryEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $accommodationId = null;

    /** @var string[] */
    #[ORM\Column(type: 'json')]
    private array $photoIds = [];

    public function getAccommodationId(): ?Uuid
    {
        return $this->accommodationId;
    }

    public function setAccommodationId(Uuid $accommodationId): static
    {
        $this->accommodationId = $accommodationId;

        return $this;
    }

    /** @return string[] */
    public function getPhotoIds(): array
    {
        return $this->photoIds;
    }

    /** @param string[] $photoIds */
    public function setPhotoIds(array $photoIds): static
    {
        $this->photoIds = $photoIds;

        return $this;
    }
}
