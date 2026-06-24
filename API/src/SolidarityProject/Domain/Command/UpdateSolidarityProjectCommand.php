<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateSolidarityProjectCommand
{
    /**
     * @param array<string, array{title: string, description: string, keyFigures: array<array{value: string|null, label: string|null}>}> $translations
     *                                                                                                                                                 translatable content keyed by locale (must contain the default locale)
     */
    public function __construct(
        public Uuid $projectId,
        public array $translations,
        public ?string $imageUrl,
        public string $status,
    ) {
    }
}
