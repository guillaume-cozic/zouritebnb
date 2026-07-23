<?php

declare(strict_types=1);

namespace App\Accommodation\Infrastructure\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineAccommodationRepository::class)]
#[ORM\Table(name: 'accommodation')]
class AccommodationEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 20)]
    private string $status = 'draft';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $street = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    #[ORM\Column(nullable: true)]
    private ?int $bedrooms = null;

    #[ORM\Column(nullable: true)]
    private ?int $bathrooms = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxGuests = null;

    #[ORM\Column(nullable: true)]
    private ?int $singleBeds = null;

    #[ORM\Column(nullable: true)]
    private ?int $doubleBeds = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $amenities = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $checkIn = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $checkOut = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $teamId = null;

    #[ORM\Column(nullable: true)]
    private ?float $weeklyPromotionPercentage = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $regionId = null;

    #[ORM\Column(length: 20, options: ['default' => 'flexible'])]
    private string $cancellationPolicy = 'flexible';

    #[ORM\Column(options: ['default' => false])]
    private bool $instantBooking = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $minNights = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxNights = null;

    #[ORM\Column(nullable: true)]
    private ?float $weekendSurchargePercentage = null;

    #[ORM\Column(nullable: true)]
    private ?float $lastMinuteDiscountPercentage = null;

    #[ORM\Column(nullable: true)]
    private ?int $lastMinuteDays = null;

    /** @var array<array{startDate: string, endDate: string, pricePerNight: float}>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $pricePeriods = null;

    /** @var array<array{name: string, price: float}>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $extraServices = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $smokingAllowed = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $petsAllowed = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $partiesAllowed = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $houseRulesNotes = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getBedrooms(): ?int
    {
        return $this->bedrooms;
    }

    public function setBedrooms(?int $bedrooms): static
    {
        $this->bedrooms = $bedrooms;

        return $this;
    }

    public function getBathrooms(): ?int
    {
        return $this->bathrooms;
    }

    public function setBathrooms(?int $bathrooms): static
    {
        $this->bathrooms = $bathrooms;

        return $this;
    }

    public function getMaxGuests(): ?int
    {
        return $this->maxGuests;
    }

    public function setMaxGuests(?int $maxGuests): static
    {
        $this->maxGuests = $maxGuests;

        return $this;
    }

    public function getSingleBeds(): ?int
    {
        return $this->singleBeds;
    }

    public function setSingleBeds(?int $singleBeds): static
    {
        $this->singleBeds = $singleBeds;

        return $this;
    }

    public function getDoubleBeds(): ?int
    {
        return $this->doubleBeds;
    }

    public function setDoubleBeds(?int $doubleBeds): static
    {
        $this->doubleBeds = $doubleBeds;

        return $this;
    }

    public function getAmenities(): ?array
    {
        return $this->amenities;
    }

    public function setAmenities(?array $amenities): static
    {
        $this->amenities = $amenities;

        return $this;
    }

    public function getCheckIn(): ?string
    {
        return $this->checkIn;
    }

    public function setCheckIn(?string $checkIn): static
    {
        $this->checkIn = $checkIn;

        return $this;
    }

    public function getCheckOut(): ?string
    {
        return $this->checkOut;
    }

    public function setCheckOut(?string $checkOut): static
    {
        $this->checkOut = $checkOut;

        return $this;
    }

    public function getTeamId(): ?Uuid
    {
        return $this->teamId;
    }

    public function setTeamId(?Uuid $teamId): static
    {
        $this->teamId = $teamId;

        return $this;
    }

    public function getWeeklyPromotionPercentage(): ?float
    {
        return $this->weeklyPromotionPercentage;
    }

    public function setWeeklyPromotionPercentage(?float $weeklyPromotionPercentage): static
    {
        $this->weeklyPromotionPercentage = $weeklyPromotionPercentage;

        return $this;
    }

    public function getRegionId(): ?Uuid
    {
        return $this->regionId;
    }

    public function setRegionId(?Uuid $regionId): static
    {
        $this->regionId = $regionId;

        return $this;
    }

    public function getCancellationPolicy(): string
    {
        return $this->cancellationPolicy;
    }

    public function setCancellationPolicy(string $cancellationPolicy): static
    {
        $this->cancellationPolicy = $cancellationPolicy;

        return $this;
    }

    public function isInstantBooking(): bool
    {
        return $this->instantBooking;
    }

    public function setInstantBooking(bool $instantBooking): static
    {
        $this->instantBooking = $instantBooking;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMinNights(): ?int
    {
        return $this->minNights;
    }

    public function setMinNights(?int $minNights): static
    {
        $this->minNights = $minNights;

        return $this;
    }

    public function getMaxNights(): ?int
    {
        return $this->maxNights;
    }

    public function setMaxNights(?int $maxNights): static
    {
        $this->maxNights = $maxNights;

        return $this;
    }

    public function getWeekendSurchargePercentage(): ?float
    {
        return $this->weekendSurchargePercentage;
    }

    public function setWeekendSurchargePercentage(?float $weekendSurchargePercentage): static
    {
        $this->weekendSurchargePercentage = $weekendSurchargePercentage;

        return $this;
    }

    public function getLastMinuteDiscountPercentage(): ?float
    {
        return $this->lastMinuteDiscountPercentage;
    }

    public function setLastMinuteDiscountPercentage(?float $lastMinuteDiscountPercentage): static
    {
        $this->lastMinuteDiscountPercentage = $lastMinuteDiscountPercentage;

        return $this;
    }

    public function getLastMinuteDays(): ?int
    {
        return $this->lastMinuteDays;
    }

    public function setLastMinuteDays(?int $lastMinuteDays): static
    {
        $this->lastMinuteDays = $lastMinuteDays;

        return $this;
    }

    /** @return array<array{startDate: string, endDate: string, pricePerNight: float}>|null */
    public function getPricePeriods(): ?array
    {
        return $this->pricePeriods;
    }

    /** @param array<array{startDate: string, endDate: string, pricePerNight: float}>|null $pricePeriods */
    public function setPricePeriods(?array $pricePeriods): static
    {
        $this->pricePeriods = $pricePeriods;

        return $this;
    }

    /** @return array<array{name: string, price: float}>|null */
    public function getExtraServices(): ?array
    {
        return $this->extraServices;
    }

    /** @param array<array{name: string, price: float}>|null $extraServices */
    public function setExtraServices(?array $extraServices): static
    {
        $this->extraServices = $extraServices;

        return $this;
    }

    public function isSmokingAllowed(): bool
    {
        return $this->smokingAllowed;
    }

    public function setSmokingAllowed(bool $smokingAllowed): static
    {
        $this->smokingAllowed = $smokingAllowed;

        return $this;
    }

    public function isPetsAllowed(): bool
    {
        return $this->petsAllowed;
    }

    public function setPetsAllowed(bool $petsAllowed): static
    {
        $this->petsAllowed = $petsAllowed;

        return $this;
    }

    public function isPartiesAllowed(): bool
    {
        return $this->partiesAllowed;
    }

    public function setPartiesAllowed(bool $partiesAllowed): static
    {
        $this->partiesAllowed = $partiesAllowed;

        return $this;
    }

    public function getHouseRulesNotes(): ?string
    {
        return $this->houseRulesNotes;
    }

    public function setHouseRulesNotes(?string $houseRulesNotes): static
    {
        $this->houseRulesNotes = $houseRulesNotes;

        return $this;
    }
}
