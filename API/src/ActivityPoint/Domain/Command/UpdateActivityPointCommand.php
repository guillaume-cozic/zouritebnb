<?php

declare(strict_types=1);

namespace App\ActivityPoint\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateActivityPointCommand
{
    public function __construct(
        public Uuid $id,
        public string $name,
        public string $description,
        public string $category,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $articleUrl,
    ) {
    }
}
