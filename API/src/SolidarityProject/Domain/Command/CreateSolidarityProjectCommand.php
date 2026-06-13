<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Command;

final readonly class CreateSolidarityProjectCommand
{
    /**
     * @param array<array{value: string|null, label: string|null}> $keyFigures
     */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $imageUrl,
        public string $status,
        public array $keyFigures = [],
    ) {
    }
}
