<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidPricePeriodException;

/**
 * A nightly price that applies to every night whose date falls within an inclusive
 * [startDate, endDate] range — the building block of seasonal / per-date pricing.
 */
final readonly class PricePeriod
{
    public function __construct(
        public string $startDate,
        public string $endDate,
        public float $pricePerNight,
    ) {
        if (!self::isValidDate($startDate) || !self::isValidDate($endDate)) {
            throw InvalidPricePeriodException::becauseInvalidDate();
        }
        if ($endDate < $startDate) {
            throw InvalidPricePeriodException::becauseEndBeforeStart();
        }
        if ($pricePerNight <= 0) {
            throw InvalidPricePeriodException::becauseNonPositivePrice($pricePerNight);
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            startDate: (string) ($data['startDate'] ?? ''),
            endDate: (string) ($data['endDate'] ?? ''),
            pricePerNight: (float) ($data['pricePerNight'] ?? 0),
        );
    }

    /** A night belongs to the period when its date is within the inclusive range. */
    public function containsNight(\DateTimeImmutable $night): bool
    {
        $date = $night->format('Y-m-d');

        return $date >= $this->startDate && $date <= $this->endDate;
    }

    /** @return array{startDate: string, endDate: string, pricePerNight: float} */
    public function toArray(): array
    {
        return [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'pricePerNight' => $this->pricePerNight,
        ];
    }

    private static function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return false !== $parsed && $parsed->format('Y-m-d') === $date;
    }
}
