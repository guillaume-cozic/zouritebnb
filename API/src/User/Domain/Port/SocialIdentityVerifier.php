<?php

declare(strict_types=1);

namespace App\User\Domain\Port;

use App\User\Domain\Entity\SocialProvider;
use App\User\Domain\Exception\SocialAuthenticationException;

interface SocialIdentityVerifier
{
    /**
     * Verifies a provider-issued token (Google ID token, Apple identity token,
     * Facebook access token) and returns the attested identity.
     *
     * @throws SocialAuthenticationException when the token is invalid, the provider
     *                                       is not configured, or no email is shared
     */
    public function verify(SocialProvider $provider, string $token): SocialIdentity;
}
