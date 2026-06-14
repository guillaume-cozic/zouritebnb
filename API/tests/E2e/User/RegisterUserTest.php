<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

final class RegisterUserTest extends UserApiTestCase
{
    public function test_should_register_user(): void
    {
        $response = self::createClient()->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'new-host@example.com',
                'password' => 'supersecret',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'email' => 'new-host@example.com',
        ]);

        $data = $response->toArray();
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('teamId', $data);
        self::assertNotEmpty($data['id']);
        self::assertNotEmpty($data['teamId']);
        // The user is logged in straight after registering: a JWT is returned.
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
    }

    public function test_should_return422_with_violation_when_email_is_invalid(): void
    {
        self::createClient()->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'not-an-email',
                'password' => 'supersecret',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['violations' => [['propertyPath' => 'email']]]);
    }

    public function test_should_return422_with_violation_when_password_is_too_short(): void
    {
        self::createClient()->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'new-host@example.com',
                'password' => 'short',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains(['violations' => [['propertyPath' => 'password']]]);
    }

    public function test_should_return422_when_email_already_exists(): void
    {
        $this->insertUser(email: 'taken@example.com');

        self::createClient()->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'taken@example.com',
                'password' => 'supersecret',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
