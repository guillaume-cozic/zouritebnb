<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid;

final readonly class AccommodationPhotoUploaded implements DomainEvent
{
    public function __construct(
        public Uuid $accommodationId,
        public Uuid $photoId,
        public string $content,
        public string $originalName,
        public string $mimeType,
        public int $size,
    ) {
    }
}
