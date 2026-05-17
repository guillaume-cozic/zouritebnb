<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Exception;

final class SolidarityProjectNotFoundException extends \DomainException
{
    public static function becauseNotFound(string $id): self
    {
        return new self(\sprintf('Solidarity project "%s" was not found.', $id));
    }
}
