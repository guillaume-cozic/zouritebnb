<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

final readonly class CreateAccommodationCommand
{
    public function __construct(
        public string $title,
        public string $description,
        public ?float $price,
    ) {
    }
}
