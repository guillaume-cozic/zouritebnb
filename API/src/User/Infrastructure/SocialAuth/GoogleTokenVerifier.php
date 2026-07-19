<?php

declare(strict_types=1);

namespace App\User\Infrastructure\SocialAuth;

use App\User\Domain\Entity\SocialProvider;
use App\User\Domain\Exception\SocialAuthenticationException;
use App\User\Domain\Port\SocialIdentity;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies a Google ID token via Google's tokeninfo endpoint, which validates the
 * signature server-side. We still check the audience (our client id) and issuer.
 */
final readonly class GoogleTokenVerifier
{
    private const string TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';
    private const array ACCEPTED_ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $clientId,
    ) {
    }

    public function verify(string $token): SocialIdentity
    {
        if ('' === $this->clientId) {
            throw SocialAuthenticationException::becauseProviderIsNotConfigured(SocialProvider::Google);
        }

        try {
            $response = $this->httpClient->request('GET', self::TOKENINFO_URL, [
                'query' => ['id_token' => $token],
            ]);
            if (200 !== $response->getStatusCode()) {
                throw SocialAuthenticationException::becauseTokenIsInvalid(SocialProvider::Google);
            }
            $claims = $response->toArray();
        } catch (SocialAuthenticationException $e) {
            throw $e;
        } catch (\Throwable) {
            throw SocialAuthenticationException::becauseTokenIsInvalid(SocialProvider::Google);
        }

        if (($claims['aud'] ?? null) !== $this->clientId
            || !\in_array($claims['iss'] ?? null, self::ACCEPTED_ISSUERS, true)) {
            throw SocialAuthenticationException::becauseTokenIsInvalid(SocialProvider::Google);
        }

        $email = $claims['email'] ?? null;
        if (!\is_string($email) || '' === $email) {
            throw SocialAuthenticationException::becauseEmailIsMissing(SocialProvider::Google);
        }

        return new SocialIdentity(
            provider: SocialProvider::Google,
            email: $email,
            firstName: \is_string($claims['given_name'] ?? null) ? $claims['given_name'] : null,
            lastName: \is_string($claims['family_name'] ?? null) ? $claims['family_name'] : null,
            emailVerified: 'true' === ($claims['email_verified'] ?? null) || true === ($claims['email_verified'] ?? null),
        );
    }
}
