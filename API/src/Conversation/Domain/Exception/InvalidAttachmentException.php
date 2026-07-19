<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Exception;

final class InvalidAttachmentException extends \DomainException
{
    public static function becauseFilenameEmpty(): self
    {
        return new self('Attachment filename is required.');
    }

    public static function becauseInvalidMimeType(string $mimeType): self
    {
        return new self(\sprintf('Attachment mime type "%s" is not allowed. Allowed: image/jpeg, image/png, image/webp.', $mimeType));
    }

    public static function becauseTooLarge(int $size, int $maxSize): self
    {
        return new self(\sprintf('Attachment is too large (%d bytes). Maximum allowed is %d bytes.', $size, $maxSize));
    }

    public static function becauseNotAnImage(): self
    {
        return new self('Attachment content is not a valid image.');
    }
}
