<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationWeeklyPromotionCommand
{
    public function __construct(
        public Uuid $accommodationId,
        public ?float $weeklyPromotionPercentage,
    ) {
    }
}
