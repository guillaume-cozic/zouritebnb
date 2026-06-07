<?php

declare(strict_types=1);

namespace App\Tests\E2e\Team;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Team\Infrastructure\Doctrine\TeamInvitationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class InviteCoHostTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function test_should_create_invitation_and_return201(): void
    {
        $teamId = Uuid::v7()->toRfc4122();

        self::createClient()->request('POST', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'alice@example.com'],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'email' => 'alice@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_should_return422_when_email_is_invalid(): void
    {
        $teamId = Uuid::v7()->toRfc4122();

        self::createClient()->request('POST', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'not-an-email'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_email_is_empty(): void
    {
        $teamId = Uuid::v7()->toRfc4122();

        self::createClient()->request('POST', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => ''],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_list_pending_invitations(): void
    {
        $teamId = Uuid::v7();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new TeamInvitationEntity()
            ->setId(Uuid::v7())
            ->setTeamId($teamId)
            ->setEmail('bob@example.com')
            ->setStatus('pending')
            ->setCreatedAt(new \DateTimeImmutable());

        $em->persist($entity);
        $em->flush();

        $response = self::createClient()->request('GET', \sprintf('/api/teams/%s/invitations', $teamId->toRfc4122()), [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        $members = $data['member'] ?? $data['hydra:member'] ?? [];
        self::assertNotEmpty($members);
        self::assertSame('bob@example.com', $members[0]['email']);
        self::assertSame('pending', $members[0]['status']);
    }
}
