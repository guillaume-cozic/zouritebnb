<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class InvalidIdentityDocumentException extends \DomainException
{
    public static function becauseInvalidMimeType(string $mimeType): self
    {
        return new self(\sprintf('Only JPEG, PNG and WebP images are allowed for identity documents, got %s.', $mimeType));
    }

    public static function becauseInvalidDocumentType(string $documentType): self
    {
        return new self(\sprintf('Invalid identity document type "%s". Allowed: passport, id_card, driving_license.', $documentType));
    }

    public static function becauseFileMissing(string $field): self
    {
        return new self(\sprintf('The "%s" file is required.', $field));
    }
}
