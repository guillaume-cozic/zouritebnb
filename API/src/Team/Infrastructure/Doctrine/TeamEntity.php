<?php

declare(strict_types=1);

namespace App\Team\Infrastructure\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineTeamRepository::class)]
#[ORM\Table(name: 'team')]
class TeamEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $favoriteSolidarityProjectId = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getFavoriteSolidarityProjectId(): ?Uuid
    {
        return $this->favoriteSolidarityProjectId;
    }

    public function setFavoriteSolidarityProjectId(?Uuid $favoriteSolidarityProjectId): static
    {
        $this->favoriteSolidarityProjectId = $favoriteSolidarityProjectId;

        return $this;
    }
}
