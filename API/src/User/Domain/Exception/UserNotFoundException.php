<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class UserNotFoundException extends \DomainException
{
    public static function becauseNotFound(string $id): self
    {
        return new self(\sprintf('User "%s" not found.', $id));
    }
}
