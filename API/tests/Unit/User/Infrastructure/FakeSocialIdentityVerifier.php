<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure;

use App\User\Domain\Entity\SocialProvider;
use App\User\Domain\Exception\SocialAuthenticationException;
use App\User\Domain\Port\SocialIdentity;
use App\User\Domain\Port\SocialIdentityVerifier;

/**
 * Deterministic SocialIdentityVerifier for tests: returns the configured identity
 * when the token matches, throws like a real verifier otherwise.
 */
final class FakeSocialIdentityVerifier implements SocialIdentityVerifier
{
    public function __construct(
        private readonly string $expectedToken,
        private readonly SocialIdentity $identity,
    ) {
    }

    public function verify(SocialProvider $provider, string $token): SocialIdentity
    {
        if ($token !== $this->expectedToken || $provider !== $this->identity->provider) {
            throw SocialAuthenticationException::becauseTokenIsInvalid($provider);
        }

        return $this->identity;
    }
}
