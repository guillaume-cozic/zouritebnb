<?php

declare(strict_types=1);

namespace App\Tests\E2e\Team;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Team\Infrastructure\Doctrine\TeamInvitationEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class InviteCoHostTest extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected static ?bool $alwaysBootKernel = true;

    /**
     * @return array{Authorization: string}
     */
    private function memberOf(string $teamId, string $email = 'host@example.com'): array
    {
        $this->createAuthUser(email: $email, teamId: $teamId);

        return $this->authHeaders($email);
    }

    public function test_should_create_invitation_and_return201(): void
    {
        $teamId = Uuid::v7()->toRfc4122();
        $headers = $this->memberOf($teamId);

        self::createClient()->request('POST', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'alice@example.com'],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertJsonContains([
            'email' => 'alice@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $teamId = Uuid::v7()->toRfc4122();

        self::createClient()->request('POST', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'alice@example.com'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_inviting_to_another_team(): void
    {
        $teamId = Uuid::v7()->toRfc4122();
        $this->createAuthUser(email: 'intruder@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('POST', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => $this->authHeaders('intruder@example.com') + ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'alice@example.com'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return422_when_email_is_invalid(): void
    {
        $teamId = Uuid::v7()->toRfc4122();
        $headers = $this->memberOf($teamId);

        self::createClient()->request('POST', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => 'not-an-email'],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_email_is_empty(): void
    {
        $teamId = Uuid::v7()->toRfc4122();
        $headers = $this->memberOf($teamId);

        self::createClient()->request('POST', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => $headers + ['Content-Type' => 'application/ld+json'],
            'json' => ['email' => ''],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_list_pending_invitations(): void
    {
        $teamId = Uuid::v7();
        $headers = $this->memberOf($teamId->toRfc4122());

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
            'headers' => $headers + ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        $members = $data['member'] ?? $data['hydra:member'] ?? [];
        self::assertNotEmpty($members);
        self::assertSame('bob@example.com', $members[0]['email']);
        self::assertSame('pending', $members[0]['status']);
    }

    public function test_should_return401_when_listing_invitations_unauthenticated(): void
    {
        $teamId = Uuid::v7()->toRfc4122();

        self::createClient()->request('GET', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_listing_invitations_of_another_team(): void
    {
        $teamId = Uuid::v7()->toRfc4122();
        $this->createAuthUser(email: 'intruder@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('GET', \sprintf('/api/teams/%s/invitations', $teamId), [
            'headers' => $this->authHeaders('intruder@example.com') + ['Accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
