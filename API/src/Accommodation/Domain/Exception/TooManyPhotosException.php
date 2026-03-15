<?php

declare(strict_types=1);

namespace App\Accommodation\Domain\Exception;

final class TooManyPhotosException extends \DomainException
{
    public static function becauseMaxReached(int $max): self
    {
        return new self(\sprintf('Maximum number of photos (%d) reached.', $max));
    }
}
