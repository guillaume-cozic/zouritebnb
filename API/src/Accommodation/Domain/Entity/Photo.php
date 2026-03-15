<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Entity;

use App\Accommodation\Domain\Exception\InvalidPhotoException;
use Symfony\Component\Uid\Uuid;

final readonly class Photo
{
    public function __construct(
        private Uuid $id,
        private Uuid $accommodationId,
        private string $filename,
        private string $originalName,
        private string $mimeType,
        private int $size,
    ) {
        if (!\in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw InvalidPhotoException::becauseInvalidMimeType($mimeType);
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAccommodationId(): Uuid
    {
        return $this->accommodationId;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
