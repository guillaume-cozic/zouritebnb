<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Exception;

final class InvalidSolidarityProjectImageException extends \DomainException
{
    public static function becauseInvalidMimeType(string $mimeType): self
    {
        return new self(\sprintf('Only JPEG, PNG and WebP images are allowed, got %s.', $mimeType));
    }

    public static function becauseTooLarge(int $size, int $maxSize): self
    {
        return new self(\sprintf('The image is too large (%d bytes), the maximum allowed size is %d bytes.', $size, $maxSize));
    }
}
