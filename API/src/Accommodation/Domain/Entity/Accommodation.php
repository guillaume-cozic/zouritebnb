<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Event\AccommodationAddressUpdated;
use App\Accommodation\Domain\Event\AccommodationAmenitiesUpdated;
use App\Accommodation\Domain\Event\AccommodationCancellationPolicyUpdated;
use App\Accommodation\Domain\Event\AccommodationCapacityUpdated;
use App\Accommodation\Domain\Event\AccommodationCheckInOutUpdated;
use App\Accommodation\Domain\Event\AccommodationDescriptionUpdated;
use App\Accommodation\Domain\Event\AccommodationGeolocationUpdated;
use App\Accommodation\Domain\Event\AccommodationInstantBookingUpdated;
use App\Accommodation\Domain\Event\AccommodationPriceUpdated;
use App\Accommodation\Domain\Event\AccommodationPublished;
use App\Accommodation\Domain\Event\AccommodationUnpublished;
use App\Accommodation\Domain\Event\AccommodationWeeklyPromotionUpdated;
use App\Accommodation\Domain\Exception\InvalidPriceException;
use App\Accommodation\Domain\Exception\InvalidWeeklyPromotionException;
use App\Shared\Domain\Entity\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Accommodation extends AggregateRoot
{
    public function __construct(
        private readonly Uuid $id,
        private string $title,
        private string $description,
        private float $price,
        private AccommodationStatus $status = AccommodationStatus::Draft,
        private ?Address $address = null,
        private ?Geolocation $geolocation = null,
        private ?Capacity $capacity = null,
        private ?Amenities $amenities = null,
        private ?CheckInOut $checkInOut = null,
        private ?Uuid $teamId = null,
        private ?float $weeklyPromotionPercentage = null,
        private ?Uuid $regionId = null,
        private CancellationPolicy $cancellationPolicy = CancellationPolicy::Flexible,
        private bool $instantBooking = false,
    ) {
        if ($price <= 0) {
            throw InvalidPriceException::becauseNegativeOrZero($price);
        }
    }

    public function getRegionId(): ?Uuid
    {
        return $this->regionId;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function updateDescription(string $title, string $description): void
    {
        $this->title = $title;
        $this->description = $description;
        $this->recordEvent(new AccommodationDescriptionUpdated($this->id));
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getStatus(): AccommodationStatus
    {
        return $this->status;
    }

    public function publish(): void
    {
        $this->status = AccommodationStatus::Published;
        $this->recordEvent(new AccommodationPublished($this->id));
    }

    public function updatePrice(float $price): void
    {
        if ($price <= 0) {
            throw InvalidPriceException::becauseNegativeOrZero($price);
        }

        $this->price = $price;
        $this->recordEvent(new AccommodationPriceUpdated($this->id));
    }

    public function unpublish(): void
    {
        $this->status = AccommodationStatus::Draft;
        $this->recordEvent(new AccommodationUnpublished($this->id));
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function updateAddress(Address $address): void
    {
        $this->address = $address;
        $this->recordEvent(new AccommodationAddressUpdated($this->id));
    }

    public function getGeolocation(): ?Geolocation
    {
        return $this->geolocation;
    }

    public function updateGeolocation(Geolocation $geolocation): void
    {
        $this->geolocation = $geolocation;
        $this->recordEvent(new AccommodationGeolocationUpdated($this->id));
    }

    public function getCapacity(): ?Capacity
    {
        return $this->capacity;
    }

    public function updateCapacity(Capacity $capacity): void
    {
        $this->capacity = $capacity;
        $this->recordEvent(new AccommodationCapacityUpdated($this->id));
    }

    public function getAmenities(): ?Amenities
    {
        return $this->amenities;
    }

    public function updateAmenities(Amenities $amenities): void
    {
        $this->amenities = $amenities;
        $this->recordEvent(new AccommodationAmenitiesUpdated($this->id));
    }

    public function getCheckInOut(): ?CheckInOut
    {
        return $this->checkInOut;
    }

    public function updateCheckInOut(CheckInOut $checkInOut): void
    {
        $this->checkInOut = $checkInOut;
        $this->recordEvent(new AccommodationCheckInOutUpdated($this->id));
    }

    public function getTeamId(): ?Uuid
    {
        return $this->teamId;
    }

    public function getWeeklyPromotionPercentage(): ?float
    {
        return $this->weeklyPromotionPercentage;
    }

    public function updateWeeklyPromotion(?float $percentage): void
    {
        if (null !== $percentage && ($percentage <= 0 || $percentage > 100)) {
            throw InvalidWeeklyPromotionException::becauseOutOfBounds($percentage);
        }

        $this->weeklyPromotionPercentage = $percentage;
        $this->recordEvent(new AccommodationWeeklyPromotionUpdated($this->id, $percentage));
    }

    public function getCancellationPolicy(): CancellationPolicy
    {
        return $this->cancellationPolicy;
    }

    public function updateCancellationPolicy(CancellationPolicy $cancellationPolicy): void
    {
        $this->cancellationPolicy = $cancellationPolicy;
        $this->recordEvent(new AccommodationCancellationPolicyUpdated($this->id));
    }

    public function isInstantBooking(): bool
    {
        return $this->instantBooking;
    }

    public function updateInstantBooking(bool $instantBooking): void
    {
        $this->instantBooking = $instantBooking;
        $this->recordEvent(new AccommodationInstantBookingUpdated($this->id));
    }
}
