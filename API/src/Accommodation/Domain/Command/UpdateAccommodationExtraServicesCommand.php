<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationExtraServicesCommand
{
    /**
     * @param array<array{name: string, price: float}> $extraServices
     */
    public function __construct(
        public Uuid $accommodationId,
        public array $extraServices,
    ) {
    }
}
