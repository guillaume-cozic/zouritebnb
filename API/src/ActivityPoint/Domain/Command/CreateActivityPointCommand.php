<?php

declare(strict_types=1);

namespace App\ActivityPoint\Domain\Command;

final readonly class CreateActivityPointCommand
{
    public function __construct(
        public string $name,
        public string $description,
        public string $category,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $articleUrl,
    ) {
    }
}
