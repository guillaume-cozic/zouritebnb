<?php

declare(strict_types=1);

namespace App\User\Infrastructure\SocialAuth;

use App\User\Domain\Entity\SocialProvider;
use App\User\Domain\Port\SocialIdentity;
use App\User\Domain\Port\SocialIdentityVerifier;

final readonly class CompositeSocialIdentityVerifier implements SocialIdentityVerifier
{
    public function __construct(
        private GoogleTokenVerifier $google,
        private AppleTokenVerifier $apple,
        private FacebookTokenVerifier $facebook,
    ) {
    }

    public function verify(SocialProvider $provider, string $token): SocialIdentity
    {
        return match ($provider) {
            SocialProvider::Google => $this->google->verify($token),
            SocialProvider::Apple => $this->apple->verify($token),
            SocialProvider::Facebook => $this->facebook->verify($token),
        };
    }
}
