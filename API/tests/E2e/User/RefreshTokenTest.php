<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

final class RefreshTokenTest extends UserApiTestCase
{
    public function test_should_issue_a_new_jwt_from_a_refresh_token(): void
    {
        $this->insertUser(email: 'host@example.com', plainPassword: 'supersecret');

        $login = self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'host@example.com', 'password' => 'supersecret'],
        ])->toArray();

        $response = self::createClient()->request('POST', '/api/token/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['refresh_token' => $login['refreshToken']],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
        // single_use rotation: a fresh refresh token is returned each time.
        self::assertArrayHasKey('refresh_token', $data);
        self::assertNotSame($login['refreshToken'], $data['refresh_token']);
    }

    public function test_should_reject_a_consumed_refresh_token(): void
    {
        $this->insertUser(email: 'host@example.com', plainPassword: 'supersecret');

        $login = self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'host@example.com', 'password' => 'supersecret'],
        ])->toArray();

        // First use rotates (and thus invalidates) the original token.
        self::createClient()->request('POST', '/api/token/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['refresh_token' => $login['refreshToken']],
        ]);

        // Re-using the now-consumed token must be refused.
        self::createClient()->request('POST', '/api/token/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['refresh_token' => $login['refreshToken']],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_reject_an_unknown_refresh_token(): void
    {
        self::createClient()->request('POST', '/api/token/refresh', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['refresh_token' => 'does-not-exist'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}
