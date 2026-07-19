<?php

declare(strict_types=1);

namespace App\User\Infrastructure\SocialAuth;

use App\User\Domain\Entity\SocialProvider;
use App\User\Domain\Exception\SocialAuthenticationException;
use App\User\Domain\Port\SocialIdentity;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies an Apple identity token (a JWT signed by Apple) against Apple's
 * published JWKS, then checks issuer and audience (our Services ID).
 *
 * Apple only shares the user's name on the very first authorization, inside the
 * sign-in response — never in the token — so the identity carries no name.
 */
final readonly class AppleTokenVerifier
{
    private const string JWKS_URL = 'https://appleid.apple.com/auth/keys';
    private const string ISSUER = 'https://appleid.apple.com';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $clientId,
    ) {
    }

    public function verify(string $token): SocialIdentity
    {
        if ('' === $this->clientId) {
            throw SocialAuthenticationException::becauseProviderIsNotConfigured(SocialProvider::Apple);
        }

        try {
            $jwks = $this->httpClient->request('GET', self::JWKS_URL)->toArray();
            $claims = (array) JWT::decode($token, JWK::parseKeySet($jwks, 'RS256'));
        } catch (\Throwable) {
            throw SocialAuthenticationException::becauseTokenIsInvalid(SocialProvider::Apple);
        }

        if (self::ISSUER !== ($claims['iss'] ?? null) || ($claims['aud'] ?? null) !== $this->clientId) {
            throw SocialAuthenticationException::becauseTokenIsInvalid(SocialProvider::Apple);
        }

        $email = $claims['email'] ?? null;
        if (!\is_string($email) || '' === $email) {
            throw SocialAuthenticationException::becauseEmailIsMissing(SocialProvider::Apple);
        }

        return new SocialIdentity(
            provider: SocialProvider::Apple,
            email: $email,
            emailVerified: 'true' === ($claims['email_verified'] ?? null) || true === ($claims['email_verified'] ?? null),
        );
    }
}
