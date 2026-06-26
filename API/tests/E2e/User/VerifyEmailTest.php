<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use App\Notification\Infrastructure\Doctrine\OutboxEmailEntity;
use Doctrine\ORM\EntityManagerInterface;

final class VerifyEmailTest extends UserApiTestCase
{
    public function test_should_verify_email_with_the_token_emailed_on_registration(): void
    {
        // Registration triggers the verification email asynchronously (sync in test).
        self::createClient()->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'newhost@example.com', 'password' => 'supersecret'],
        ]);
        self::assertResponseIsSuccessful();

        $token = $this->verificationTokenFor('newhost@example.com');

        self::createClient()->request('POST', '/api/verify-email', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['token' => $token],
        ]);
        self::assertResponseStatusCodeSame(204);

        $response = self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'newhost@example.com', 'password' => 'supersecret'],
        ]);
        self::assertResponseIsSuccessful();
        self::assertTrue($response->toArray()['emailVerified']);
    }

    public function test_should_expose_email_as_unverified_before_verification(): void
    {
        self::createClient()->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'fresh@example.com', 'password' => 'supersecret'],
        ]);

        $response = self::createClient()->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'fresh@example.com', 'password' => 'supersecret'],
        ]);

        self::assertFalse($response->toArray()['emailVerified']);
    }

    public function test_should_return422_for_an_unknown_token(): void
    {
        self::createClient()->request('POST', '/api/verify-email', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['token' => 'does-not-exist'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    private function verificationTokenFor(string $email): string
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($em instanceof EntityManagerInterface);

        foreach ($em->getRepository(OutboxEmailEntity::class)->findBy(['recipientEmail' => $email]) as $outboxEmail) {
            if (preg_match('#/verify-email\?token=([^"\s]+)#', (string) $outboxEmail->getHtmlBody(), $matches)) {
                return urldecode($matches[1]);
            }
        }

        self::fail('No verification email was queued.');
    }
}
