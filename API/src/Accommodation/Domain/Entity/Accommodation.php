<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Event\AccommodationAddressUpdated;
use App\Accommodation\Domain\Event\AccommodationGeolocationUpdated;
use App\Accommodation\Domain\Event\AccommodationPriceUpdated;
use App\Accommodation\Domain\Event\AccommodationPublished;
use App\Accommodation\Domain\Event\AccommodationUnpublished;
use App\Accommodation\Domain\Exception\InvalidPriceException;
use App\Shared\Domain\Entity\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class Accommodation extends AggregateRoot
{
    public function __construct(
        private readonly Uuid $id,
        private readonly string $title,
        private readonly string $description,
        private float $price,
        private AccommodationStatus $status = AccommodationStatus::Draft,
        private ?Address $address = null,
        private ?Geolocation $geolocation = null,
    ) {
        if ($price <= 0) {
            throw InvalidPriceException::becauseNegativeOrZero($price);
        }
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
}
