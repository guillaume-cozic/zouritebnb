<?php

declare(strict_types=1);

namespace App\Wishlist\Domain\Exception;

final class WishlistException extends \DomainException
{
    public static function becauseAccommodationNotFound(): self
    {
        return new self('Accommodation not found.');
    }
}
