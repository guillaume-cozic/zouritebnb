<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class ReservationEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $accommodationId = null;

    #[ORM\Column(type: UuidType::NAME)]
    private ?Uuid $teamId = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $guestUserId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $checkIn = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $checkOut = null;

    #[ORM\Column(length: 255)]
    private ?string $guestName = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(options: ['default' => 0])]
    private float $totalPrice = 0.0;

    #[ORM\Column(options: ['default' => 0])]
    private float $pricePerNight = 0.0;

    #[ORM\Column(nullable: true)]
    private ?float $appliedDiscountPercentage = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

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

    public function getTeamId(): ?Uuid
    {
        return $this->teamId;
    }

    public function setTeamId(Uuid $teamId): static
    {
        $this->teamId = $teamId;

        return $this;
    }

    public function getGuestUserId(): ?Uuid
    {
        return $this->guestUserId;
    }

    public function setGuestUserId(?Uuid $guestUserId): static
    {
        $this->guestUserId = $guestUserId;

        return $this;
    }

    public function getCheckIn(): ?\DateTimeImmutable
    {
        return $this->checkIn;
    }

    public function setCheckIn(\DateTimeImmutable $checkIn): static
    {
        $this->checkIn = $checkIn;

        return $this;
    }

    public function getCheckOut(): ?\DateTimeImmutable
    {
        return $this->checkOut;
    }

    public function setCheckOut(\DateTimeImmutable $checkOut): static
    {
        $this->checkOut = $checkOut;

        return $this;
    }

    public function getGuestName(): ?string
    {
        return $this->guestName;
    }

    public function setGuestName(string $guestName): static
    {
        $this->guestName = $guestName;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getPricePerNight(): float
    {
        return $this->pricePerNight;
    }

    public function setPricePerNight(float $pricePerNight): static
    {
        $this->pricePerNight = $pricePerNight;

        return $this;
    }

    public function getAppliedDiscountPercentage(): ?float
    {
        return $this->appliedDiscountPercentage;
    }

    public function setAppliedDiscountPercentage(?float $appliedDiscountPercentage): static
    {
        $this->appliedDiscountPercentage = $appliedDiscountPercentage;

        return $this;
    }
}
