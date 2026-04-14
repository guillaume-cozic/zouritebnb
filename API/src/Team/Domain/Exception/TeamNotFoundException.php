<?php

declare(strict_types=1);

namespace App\Team\Domain\Exception;

final class TeamNotFoundException extends \DomainException
{
    public static function becauseNotFound(string $id): self
    {
        return new self(\sprintf('Team "%s" not found.', $id));
    }
}
