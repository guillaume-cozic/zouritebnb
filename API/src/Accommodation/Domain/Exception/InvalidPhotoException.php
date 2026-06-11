<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class InvalidPhotoException extends \DomainException
{
    public static function becauseInvalidMimeType(string $mimeType): self
    {
        return new self(\sprintf('Only JPEG, PNG and WebP images are allowed, got %s.', $mimeType));
    }

    public static function becauseTooLarge(int $size, int $maxSize): self
    {
        return new self(\sprintf('The photo is too large (%d bytes), the maximum allowed size is %d bytes.', $size, $maxSize));
    }
}
