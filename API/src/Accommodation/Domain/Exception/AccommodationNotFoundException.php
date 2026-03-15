<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class AccommodationNotFoundException extends \DomainException
{
    public static function becauseNotFound(string $id): self
    {
        return new self(\sprintf('Accommodation "%s" not found.', $id));
    }
}
