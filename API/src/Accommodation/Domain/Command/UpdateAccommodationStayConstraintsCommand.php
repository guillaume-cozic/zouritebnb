<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationStayConstraintsCommand
{
    public function __construct(
        public Uuid $accommodationId,
        public ?int $minNights,
        public ?int $maxNights,
    ) {
    }
}
