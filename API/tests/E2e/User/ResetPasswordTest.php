<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use App\Notification\Infrastructure\Doctrine\OutboxEmailEntity;
use Doctrine\ORM\EntityManagerInterface;

final class ResetPasswordTest extends UserApiTestCase
{
    public function test_should_reset_password_with_the_emailed_token(): void
    {
        $this->insertUser(email: 'host@example.com', plainPassword: 'old-password');
        $token = $this->requestResetTokenFor('host@example.com');

        self::createClient()->request('POST', '/api/reset-password', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['token' => $token, 'password' => 'brand-new-password'],
        ]);

        self::assertResponseStatusCodeSame(204);

        // The new password works...
        self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'host@example.com', 'password' => 'brand-new-password'],
        ]);
        self::assertResponseIsSuccessful();

        // ...and the old one no longer does.
        self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'host@example.com', 'password' => 'old-password'],
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_reject_a_reused_token(): void
    {
        $this->insertUser(email: 'host@example.com', plainPassword: 'old-password');
        $token = $this->requestResetTokenFor('host@example.com');

        $payload = [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['token' => $token, 'password' => 'brand-new-password'],
        ];

        self::createClient()->request('POST', '/api/reset-password', $payload);
        self::assertResponseStatusCodeSame(204);

        // A single-use token cannot be replayed.
        self::createClient()->request('POST', '/api/reset-password', $payload);
        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_for_an_unknown_token(): void
    {
        self::createClient()->request('POST', '/api/reset-password', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['token' => 'does-not-exist', 'password' => 'brand-new-password'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_new_password_is_too_short(): void
    {
        $this->insertUser(email: 'host@example.com');
        $token = $this->requestResetTokenFor('host@example.com');

        self::createClient()->request('POST', '/api/reset-password', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['token' => $token, 'password' => 'short'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * Drives the public "forgot password" endpoint and extracts the raw token from the
     * link in the queued email — exactly what a real user receives in their inbox.
     */
    private function requestResetTokenFor(string $email): string
    {
        self::createClient()->request('POST', '/api/forgot-password', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => $email],
        ]);

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($em instanceof EntityManagerInterface);

        foreach ($em->getRepository(OutboxEmailEntity::class)->findAll() as $outboxEmail) {
            if (preg_match('#/reset-password\?token=([^"\s]+)#', (string) $outboxEmail->getHtmlBody(), $matches)) {
                return urldecode($matches[1]);
            }
        }

        self::fail('No password reset email was queued.');
    }
}
