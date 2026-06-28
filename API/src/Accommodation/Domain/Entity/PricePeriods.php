<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidPricePeriodException;

/**
 * Ordered collection of price periods for an accommodation. When several periods
 * cover the same night, the first match (in list order) wins — overlaps are allowed
 * and resolved deterministically rather than rejected.
 */
final readonly class PricePeriods
{
    /** @var PricePeriod[] */
    private array $periods;

    /**
     * @param PricePeriod[] $periods
     */
    public function __construct(array $periods)
    {
        foreach ($periods as $period) {
            if (!$period instanceof PricePeriod) {
                throw InvalidPricePeriodException::becauseInvalidItem();
            }
        }

        $this->periods = array_values($periods);
    }

    /**
     * @param array<array{startDate?: string, endDate?: string, pricePerNight?: float}> $list
     */
    public static function fromArray(array $list): self
    {
        return new self(array_map(
            static fn (array $data): PricePeriod => PricePeriod::fromArray($data),
            array_values($list),
        ));
    }

    /** Nightly price overriding the base for that night, or null when no period covers it. */
    public function priceForNight(\DateTimeImmutable $night): ?float
    {
        foreach ($this->periods as $period) {
            if ($period->containsNight($night)) {
                return $period->pricePerNight;
            }
        }

        return null;
    }

    /** @return array<array{startDate: string, endDate: string, pricePerNight: float}> */
    public function toArray(): array
    {
        return array_map(static fn (PricePeriod $period): array => $period->toArray(), $this->periods);
    }

    public function isEmpty(): bool
    {
        return [] === $this->periods;
    }
}
