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

    #[ORM\Column(length: 34, nullable: true)]
    private ?string $iban = null;

    #[ORM\Column(length: 11, nullable: true)]
    private ?string $bic = null;

    #[ORM\Column(length: 70, nullable: true)]
    private ?string $bankAccountHolderName = null;

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

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): static
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): static
    {
        $this->bic = $bic;

        return $this;
    }

    public function getBankAccountHolderName(): ?string
    {
        return $this->bankAccountHolderName;
    }

    public function setBankAccountHolderName(?string $bankAccountHolderName): static
    {
        $this->bankAccountHolderName = $bankAccountHolderName;

        return $this;
    }
}
