<?php

declare(strict_types=1);

namespace App\ActivityPoint\Domain\Exception;

final class InvalidActivityPointException extends \DomainException
{
    public static function becauseNameIsBlank(): self
    {
        return new self('Activity point name must not be blank.');
    }

    public static function becauseDescriptionIsBlank(): self
    {
        return new self('Activity point description must not be blank.');
    }

    public static function becauseCategoryIsInvalid(string $category): self
    {
        return new self(\sprintf('Activity point category "%s" is not supported.', $category));
    }

    public static function becauseLatitudeIsMissing(): self
    {
        return new self('Activity point latitude is required.');
    }

    public static function becauseLongitudeIsMissing(): self
    {
        return new self('Activity point longitude is required.');
    }

    public static function becauseLatitudeIsOutOfBounds(float $latitude): self
    {
        return new self(\sprintf('Activity point latitude must be within Rodrigues bounds (-20.05 to -19.35), got %s.', $latitude));
    }

    public static function becauseLongitudeIsOutOfBounds(float $longitude): self
    {
        return new self(\sprintf('Activity point longitude must be within Rodrigues bounds (62.95 to 63.95), got %s.', $longitude));
    }

    public static function becauseArticleUrlIsBlank(): self
    {
        return new self('Activity point article URL must not be blank when provided.');
    }

    public static function becauseArticleUrlIsInvalid(string $articleUrl): self
    {
        return new self(\sprintf('Activity point article URL must start with "http://", "https://" or "/", got "%s".', $articleUrl));
    }
}
