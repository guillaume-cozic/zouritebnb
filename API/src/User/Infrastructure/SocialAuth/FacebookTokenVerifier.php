<?php

declare(strict_types=1);

namespace App\User\Infrastructure\SocialAuth;

use App\User\Domain\Entity\SocialProvider;
use App\User\Domain\Exception\SocialAuthenticationException;
use App\User\Domain\Port\SocialIdentity;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies a Facebook access token: first against the debug_token endpoint using
 * the app credentials (proves the token was issued for OUR app and is still
 * valid), then fetches the profile to obtain the email and name.
 */
final readonly class FacebookTokenVerifier
{
    private const string GRAPH_URL = 'https://graph.facebook.com/v19.0';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $appId,
        private string $appSecret,
    ) {
    }

    public function verify(string $token): SocialIdentity
    {
        if ('' === $this->appId || '' === $this->appSecret) {
            throw SocialAuthenticationException::becauseProviderIsNotConfigured(SocialProvider::Facebook);
        }

        try {
            $debug = $this->httpClient->request('GET', self::GRAPH_URL.'/debug_token', [
                'query' => [
                    'input_token' => $token,
                    'access_token' => $this->appId.'|'.$this->appSecret,
                ],
            ])->toArray();
        } catch (\Throwable) {
            throw SocialAuthenticationException::becauseTokenIsInvalid(SocialProvider::Facebook);
        }

        $data = $debug['data'] ?? [];
        if (true !== ($data['is_valid'] ?? false) || ($data['app_id'] ?? null) !== $this->appId) {
            throw SocialAuthenticationException::becauseTokenIsInvalid(SocialProvider::Facebook);
        }

        try {
            $profile = $this->httpClient->request('GET', self::GRAPH_URL.'/me', [
                'query' => [
                    'fields' => 'id,email,first_name,last_name',
                    'access_token' => $token,
                ],
            ])->toArray();
        } catch (\Throwable) {
            throw SocialAuthenticationException::becauseTokenIsInvalid(SocialProvider::Facebook);
        }

        $email = $profile['email'] ?? null;
        if (!\is_string($email) || '' === $email) {
            // Facebook lets users deny the email permission or register by phone only.
            throw SocialAuthenticationException::becauseEmailIsMissing(SocialProvider::Facebook);
        }

        return new SocialIdentity(
            provider: SocialProvider::Facebook,
            email: $email,
            firstName: \is_string($profile['first_name'] ?? null) ? $profile['first_name'] : null,
            lastName: \is_string($profile['last_name'] ?? null) ? $profile['last_name'] : null,
            // A Facebook account's email is confirmed by Facebook itself.
            emailVerified: true,
        );
    }
}
