<?php

declare(strict_types=1);

namespace App\Shared\Domain\Port;

use Symfony\Component\Uid\Uuid;

final readonly class AccommodationSummary
{
    public function __construct(
        public Uuid $accommodationId,
        public string $title,
        public ?string $city,
    ) {
    }
}
