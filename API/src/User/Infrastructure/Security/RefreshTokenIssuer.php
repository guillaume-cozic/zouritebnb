<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Mints and persists a refresh token for a freshly authenticated user.
 *
 * Login / register / social sign-in all return a refresh token alongside the JWT
 * so the front can renew the access token silently; this centralises that logic.
 */
final readonly class RefreshTokenIssuer
{
    public function __construct(
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private int $refreshTokenTtl,
    ) {
    }

    public function issueFor(UserInterface $user): string
    {
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->refreshTokenTtl);
        $this->refreshTokenManager->save($refreshToken);

        return (string) $refreshToken->getRefreshToken();
    }
}
