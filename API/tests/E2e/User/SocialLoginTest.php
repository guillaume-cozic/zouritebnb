<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use App\Tests\Unit\User\Infrastructure\FakeSocialIdentityVerifier;
use App\User\Domain\Entity\SocialProvider;
use App\User\Domain\Port\SocialIdentity;
use App\User\Domain\Port\SocialIdentityVerifier;

final class SocialLoginTest extends UserApiTestCase
{
    public function test_should_register_and_authenticate_a_new_social_user(): void
    {
        $client = self::createClient();
        $this->useFakeVerifier(new SocialIdentity(
            provider: SocialProvider::Google,
            email: 'social@example.com',
            firstName: 'Jane',
            lastName: 'Doe',
            emailVerified: true,
        ));

        $response = $client->request('POST', '/api/auth/social', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['provider' => 'google', 'token' => 'valid-token'],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame('social@example.com', $data['email']);
        self::assertSame('Jane', $data['firstName']);
        self::assertTrue($data['emailVerified']);
        self::assertNotEmpty($data['token']);
        self::assertNotEmpty($data['teamId']);
    }

    public function test_should_log_in_existing_user_with_same_email(): void
    {
        $client = self::createClient();
        $userId = $this->createAuthUser(email: 'existing@example.com');
        $this->useFakeVerifier(new SocialIdentity(
            provider: SocialProvider::Google,
            email: 'existing@example.com',
        ));

        $response = $client->request('POST', '/api/auth/social', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['provider' => 'google', 'token' => 'valid-token'],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame($userId, $data['id']);
        self::assertNotEmpty($data['token']);
    }

    public function test_should_reject_invalid_provider_token(): void
    {
        $client = self::createClient();
        $this->useFakeVerifier(new SocialIdentity(
            provider: SocialProvider::Google,
            email: 'social@example.com',
        ));

        $client->request('POST', '/api/auth/social', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['provider' => 'google', 'token' => 'forged-token'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_reject_unknown_provider(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/auth/social', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['provider' => 'myspace', 'token' => 'whatever'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    private function useFakeVerifier(SocialIdentity $identity): void
    {
        self::getContainer()->set(
            SocialIdentityVerifier::class,
            new FakeSocialIdentityVerifier('valid-token', $identity),
        );
    }
}
