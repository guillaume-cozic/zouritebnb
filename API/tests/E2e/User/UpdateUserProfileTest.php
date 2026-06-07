<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use Symfony\Component\Uid\Uuid;

final class UpdateUserProfileTest extends UserApiTestCase
{
    public function test_should_update_user_profile(): void
    {
        $id = $this->insertUser(email: 'host@example.com', plainPassword: 'supersecret');

        self::createClient()->request('PATCH', '/api/users/'.$id.'/profile', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'firstName' => 'Marie',
                'lastName' => 'Dupont',
                'email' => 'marie@example.com',
            ],
        ]);

        self::assertResponseStatusCodeSame(204);

        self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'email' => 'marie@example.com',
                'password' => 'supersecret',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'email' => 'marie@example.com',
            'firstName' => 'Marie',
            'lastName' => 'Dupont',
        ]);
    }

    public function test_should_return422_when_user_does_not_exist(): void
    {
        self::createClient()->request('PATCH', '/api/users/'.Uuid::v7()->toRfc4122().'/profile', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'firstName' => 'Marie',
                'lastName' => 'Dupont',
                'email' => 'marie@example.com',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_email_is_already_taken_by_another_user(): void
    {
        $this->insertUser(email: 'taken@example.com');
        $id = $this->insertUser(email: 'host@example.com');

        self::createClient()->request('PATCH', '/api/users/'.$id.'/profile', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'firstName' => 'Marie',
                'lastName' => 'Dupont',
                'email' => 'taken@example.com',
            ],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
