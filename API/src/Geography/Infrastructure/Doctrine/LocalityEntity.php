<?php

declare(strict_types=1);

namespace App\Geography\Infrastructure\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineLocalityRepository::class)]
#[ORM\Table(name: 'locality')]
#[ORM\Index(name: 'IDX_locality_region', columns: ['region_id'])]
class LocalityEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(name: 'region_id', type: UuidType::NAME)]
    private ?Uuid $regionId = null;

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

    public function getRegionId(): ?Uuid
    {
        return $this->regionId;
    }

    public function setRegionId(Uuid $regionId): static
    {
        $this->regionId = $regionId;

        return $this;
    }
}
