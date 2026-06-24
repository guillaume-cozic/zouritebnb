<?php

declare(strict_types=1);

namespace App\SolidarityProject\Domain\Exception;

final class InvalidSolidarityProjectException extends \DomainException
{
    public static function becauseTitleIsBlank(): self
    {
        return new self('Solidarity project title must not be blank.');
    }

    public static function becauseDescriptionIsBlank(): self
    {
        return new self('Solidarity project description must not be blank.');
    }

    public static function becauseStatusIsInvalid(string $status): self
    {
        return new self(\sprintf('Solidarity project status must be "active" or "closed", got "%s".', $status));
    }

    public static function becauseImageUrlIsBlank(): self
    {
        return new self('Solidarity project image URL must not be blank when provided.');
    }

    public static function becauseDefaultTranslationIsMissing(string $defaultLocale): self
    {
        return new self(\sprintf('Solidarity project must have a translation for the default locale "%s".', $defaultLocale));
    }

    public static function becauseLocaleIsUnsupported(string $locale): self
    {
        return new self(\sprintf('Solidarity project locale "%s" is not supported.', $locale));
    }

    public static function becauseTranslationIsInvalid(string $locale): self
    {
        return new self(\sprintf('Solidarity project translation for locale "%s" must be a ProjectTranslation instance.', $locale));
    }
}
