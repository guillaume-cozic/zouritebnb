<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity;

/**
 * A date change requested by the guest and awaiting the host's approval: the
 * proposed range and its recomputed (frozen) price. Applied onto the reservation
 * when approved, discarded when rejected.
 */
final readonly class PendingModification
{
    public function __construct(
        public DateRange $dateRange,
        public ReservationPrice $price,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            dateRange: new DateRange(
                new \DateTimeImmutable((string) $data['checkIn']),
                new \DateTimeImmutable((string) $data['checkOut']),
            ),
            price: new ReservationPrice(
                totalPrice: (float) $data['totalPrice'],
                pricePerNight: (float) $data['pricePerNight'],
                appliedDiscountPercentage: isset($data['appliedDiscountPercentage']) ? (float) $data['appliedDiscountPercentage'] : null,
                commissionAmount: (float) ($data['commissionAmount'] ?? 0),
                donationAmount: (float) ($data['donationAmount'] ?? 0),
                extraServicesTotal: (float) ($data['extraServicesTotal'] ?? 0),
            ),
        );
    }

    public function totalPaid(): float
    {
        return round($this->price->totalPrice + $this->price->commissionAmount + $this->price->donationAmount, 2);
    }

    /** @return array{checkIn: string, checkOut: string, totalPrice: float, pricePerNight: float, appliedDiscountPercentage: float|null, commissionAmount: float, donationAmount: float, extraServicesTotal: float} */
    public function toArray(): array
    {
        return [
            'checkIn' => $this->dateRange->checkIn()->format(\DateTimeInterface::ATOM),
            'checkOut' => $this->dateRange->checkOut()->format(\DateTimeInterface::ATOM),
            'totalPrice' => $this->price->totalPrice,
            'pricePerNight' => $this->price->pricePerNight,
            'appliedDiscountPercentage' => $this->price->appliedDiscountPercentage,
            'commissionAmount' => $this->price->commissionAmount,
            'donationAmount' => $this->price->donationAmount,
            'extraServicesTotal' => $this->price->extraServicesTotal,
        ];
    }
}
