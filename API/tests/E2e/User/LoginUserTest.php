<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

final class LoginUserTest extends UserApiTestCase
{
    public function test_should_authenticate_user(): void
    {
        $id = $this->insertUser(
            email: 'host@example.com',
            plainPassword: 'supersecret',
            firstName: 'Marie',
            lastName: 'Dupont',
        );

        $response = self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'host@example.com',
                'password' => 'supersecret',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'email' => 'host@example.com',
            'firstName' => 'Marie',
            'lastName' => 'Dupont',
        ]);

        $data = $response->toArray();
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
        // A refresh token is issued so the session can be renewed silently.
        self::assertArrayHasKey('refreshToken', $data);
        self::assertNotEmpty($data['refreshToken']);
    }

    public function test_should_return422_when_password_is_wrong(): void
    {
        $this->insertUser(email: 'host@example.com', plainPassword: 'supersecret');

        self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'host@example.com',
                'password' => 'wrong-password',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_email_is_unknown(): void
    {
        self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'unknown@example.com',
                'password' => 'supersecret',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
