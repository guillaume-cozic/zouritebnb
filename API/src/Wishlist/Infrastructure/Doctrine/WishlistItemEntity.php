<?php

declare(strict_types=1);

namespace App\Wishlist\Infrastructure\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineWishlistRepository::class)]
#[ORM\Table(name: 'wishlist_item')]
#[ORM\UniqueConstraint(name: 'uniq_wishlist_user_accommodation', columns: ['user_id', 'accommodation_id'])]
#[ORM\UniqueConstraint(name: 'uniq_wishlist_correlation_accommodation', columns: ['correlation_id', 'accommodation_id'])]
class WishlistItemEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $userId = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $correlationId = null;

    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $accommodationId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }

    public function setUserId(?Uuid $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCorrelationId(): ?Uuid
    {
        return $this->correlationId;
    }

    public function setCorrelationId(?Uuid $correlationId): static
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    public function getAccommodationId(): ?Uuid
    {
        return $this->accommodationId;
    }

    public function setAccommodationId(Uuid $accommodationId): static
    {
        $this->accommodationId = $accommodationId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
