<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Exception;

final class InvalidKeyFigureException extends \DomainException
{
    public static function becauseValueIsBlank(): self
    {
        return new self('Key figure value is required.');
    }

    public static function becauseLabelIsBlank(): self
    {
        return new self('Key figure label is required.');
    }
}
