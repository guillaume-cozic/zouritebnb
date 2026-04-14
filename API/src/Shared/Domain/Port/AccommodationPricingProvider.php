<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

interface AccommodationPricingProvider
{
    public function findByAccommodationId(Uuid $id): ?AccommodationPricing;
}
