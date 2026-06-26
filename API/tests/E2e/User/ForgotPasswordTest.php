<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use App\Notification\Infrastructure\Doctrine\OutboxEmailEntity;
use App\User\Infrastructure\Doctrine\UserTokenEntity;
use Doctrine\ORM\EntityManagerInterface;

final class ForgotPasswordTest extends UserApiTestCase
{
    public function test_should_queue_a_reset_email_and_issue_a_token_for_a_known_email(): void
    {
        $this->insertUser(email: 'host@example.com');

        self::createClient()->request('POST', '/api/forgot-password', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'host@example.com'],
        ]);

        self::assertResponseStatusCodeSame(202);

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($em instanceof EntityManagerInterface);

        $tokens = $em->getRepository(UserTokenEntity::class)->findBy(['purpose' => 'password_reset']);
        self::assertCount(1, $tokens);

        $emails = $em->getRepository(OutboxEmailEntity::class)->findAll();
        $resetEmails = array_filter($emails, static fn (OutboxEmailEntity $e): bool => str_contains((string) $e->getHtmlBody(), '/reset-password?token='));
        self::assertCount(1, $resetEmails);
        self::assertSame('host@example.com', reset($resetEmails)->getRecipientEmail());
    }

    public function test_should_respond202_without_issuing_a_token_for_an_unknown_email(): void
    {
        self::createClient()->request('POST', '/api/forgot-password', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'ghost@example.com'],
        ]);

        self::assertResponseStatusCodeSame(202);

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($em instanceof EntityManagerInterface);
        self::assertCount(0, $em->getRepository(UserTokenEntity::class)->findAll());
    }

    public function test_should_return422_when_email_is_invalid(): void
    {
        self::createClient()->request('POST', '/api/forgot-password', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'not-an-email'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
