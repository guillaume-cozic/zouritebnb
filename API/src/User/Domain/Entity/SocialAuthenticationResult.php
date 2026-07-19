<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

/**
 * Outcome of a social sign-in: the (possibly freshly registered) user and
 * whether the account was created by this authentication.
 */
final readonly class SocialAuthenticationResult
{
    public function __construct(
        public User $user,
        public bool $registered,
    ) {
    }
}
