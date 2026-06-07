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
