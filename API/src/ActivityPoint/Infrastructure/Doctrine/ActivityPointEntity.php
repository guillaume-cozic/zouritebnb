<?php

declare(strict_types=1);

namespace App\ActivityPoint\Infrastructure\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineActivityPointRepository::class)]
#[ORM\Table(name: 'activity_point')]
class ActivityPointEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 32)]
    private ?string $category = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $longitude = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $articleUrl = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getArticleUrl(): ?string
    {
        return $this->articleUrl;
    }

    public function setArticleUrl(?string $articleUrl): static
    {
        $this->articleUrl = $articleUrl;

        return $this;
    }
}
