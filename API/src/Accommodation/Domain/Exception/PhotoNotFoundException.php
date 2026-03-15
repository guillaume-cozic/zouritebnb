<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class PhotoNotFoundException extends \DomainException
{
    public static function becauseNotFound(string $id): self
    {
        return new self(\sprintf('Photo "%s" not found.', $id));
    }
}
