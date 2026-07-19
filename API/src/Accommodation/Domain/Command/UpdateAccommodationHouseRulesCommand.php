<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAccommodationHouseRulesCommand
{
    public function __construct(
        public Uuid $accommodationId,
        public bool $smokingAllowed,
        public bool $petsAllowed,
        public bool $partiesAllowed,
        public ?string $houseRulesNotes,
    ) {
    }
}
