<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationAmenitiesCommand
{
    public function __construct(
        public Uuid $id,
        public array $codes,
    ) {
    }
}
