<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationDynamicPricingCommand
{
    public function __construct(
        public Uuid $accommodationId,
        public ?float $weekendSurchargePercentage,
        public ?float $lastMinuteDiscountPercentage,
        public ?int $lastMinuteDays,
    ) {
    }
}
