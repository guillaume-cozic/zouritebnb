<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UploadAccommodationPhotoCommand
{
    public function __construct(
        public Uuid $accommodationId,
        public string $content,
        public string $originalName,
        public string $mimeType,
        public int $size,
    ) {
    }
}
