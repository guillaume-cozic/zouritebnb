<?php

declare(strict_types=1);

namespace App\Geography\Domain\Exception;

final class InvalidLocalityException extends \DomainException
{
    public static function becauseNameBlank(): self
    {
        return new self('Locality name must not be blank.');
    }
}
