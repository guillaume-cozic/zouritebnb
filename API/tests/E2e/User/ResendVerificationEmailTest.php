<?php

declare(strict_types=1);

namespace App\Tests\E2e\User;

use App\User\Infrastructure\Doctrine\UserTokenEntity;
use Doctrine\ORM\EntityManagerInterface;

final class ResendVerificationEmailTest extends UserApiTestCase
{
    public function test_should_issue_a_fresh_verification_token_for_the_authenticated_user(): void
    {
        $this->insertUser(email: 'host@example.com');

        self::createClient()->request('POST', '/api/users/resend-verification-email', [
            'headers' => $this->authHeaders('host@example.com'),
        ]);

        self::assertResponseStatusCodeSame(202);

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($em instanceof EntityManagerInterface);
        self::assertCount(1, $em->getRepository(UserTokenEntity::class)->findBy(['purpose' => 'email_verification']));
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        self::createClient()->request('POST', '/api/users/resend-verification-email', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}
