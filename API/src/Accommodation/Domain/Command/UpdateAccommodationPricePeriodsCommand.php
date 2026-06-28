<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationPricePeriodsCommand
{
    /**
     * @param array<array{startDate: string, endDate: string, pricePerNight: float}> $pricePeriods
     */
    public function __construct(
        public Uuid $accommodationId,
        public array $pricePeriods,
    ) {
    }
}
