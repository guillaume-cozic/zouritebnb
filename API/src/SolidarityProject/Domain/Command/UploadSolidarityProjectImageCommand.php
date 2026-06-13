<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Command;

final readonly class UploadSolidarityProjectImageCommand
{
    public function __construct(
        public string $content,
        public string $mimeType,
        public int $size,
    ) {
    }
}
