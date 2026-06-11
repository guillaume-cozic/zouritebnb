<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\Exception\InvalidIdentityDocumentException;

/**
 * Raw identity document (ID card / passport / driving licence or selfie) uploaded by a user.
 *
 * Transient value object: it carries the file bytes through the verification use case and is
 * persisted to secure storage by a listener. It is never mapped to a database table.
 */
final readonly class IdentityDocument
{
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Hard cap on the uploaded document size (10 MB) to prevent memory-exhaustion DoS. */
    private const int MAX_SIZE_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private string $content,
        private string $originalName,
        private string $mimeType,
        private int $size,
    ) {
        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw InvalidIdentityDocumentException::becauseInvalidMimeType($mimeType);
        }

        if ($size > self::MAX_SIZE_BYTES) {
            throw InvalidIdentityDocumentException::becauseTooLarge($size, self::MAX_SIZE_BYTES);
        }
    }

    public function getContent(): string
    {
        return $this->content;
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

    public function extension(): string
    {
        return match ($this->mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
