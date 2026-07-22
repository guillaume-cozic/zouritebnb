<?php

declare(strict_types=1);

namespace App\ActivityPoint\Domain\Exception;

final class ActivityPointNotFoundException extends \DomainException
{
    public static function becauseNotFound(string $id): self
    {
        return new self(\sprintf('Activity point "%s" was not found.', $id));
    }
}
