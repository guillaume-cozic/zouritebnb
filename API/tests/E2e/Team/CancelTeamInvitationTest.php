<?php

declare(strict_types=1);

namespace App\Tests\E2e\Team;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Team\Infrastructure\Doctrine\TeamInvitationEntity;
use App\Tests\E2e\AuthenticatedClientTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class CancelTeamInvitationTest extends ApiTestCase
{
    use AuthenticatedClientTrait;

    protected static ?bool $alwaysBootKernel = true;

    private function insertInvitation(Uuid $invitationId, Uuid $teamId, string $email, string $status): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new TeamInvitationEntity()
            ->setId($invitationId)
            ->setTeamId($teamId)
            ->setEmail($email)
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable());

        $em->persist($entity);
        $em->flush();
    }

    /**
     * @return array{Authorization: string}
     */
    private function memberOf(Uuid $teamId, string $email = 'host@example.com'): array
    {
        $this->createAuthUser(email: $email, teamId: $teamId->toRfc4122());

        return $this->authHeaders($email);
    }

    public function test_should_cancel_pending_invitation_and_return204(): void
    {
        $invitationId = Uuid::v7();
        $teamId = Uuid::v7();
        $headers = $this->memberOf($teamId);
        $this->insertInvitation($invitationId, $teamId, 'alice@example.com', 'pending');

        self::createClient()->request('DELETE', \sprintf('/api/team-invitations/%s', $invitationId->toRfc4122()), [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(204);
    }

    public function test_should_return401_when_not_authenticated(): void
    {
        $invitationId = Uuid::v7();
        $teamId = Uuid::v7();
        $this->insertInvitation($invitationId, $teamId, 'alice@example.com', 'pending');

        self::createClient()->request('DELETE', \sprintf('/api/team-invitations/%s', $invitationId->toRfc4122()));

        self::assertResponseStatusCodeSame(401);
    }

    public function test_should_return403_when_cancelling_invitation_of_another_team(): void
    {
        $invitationId = Uuid::v7();
        $teamId = Uuid::v7();
        $this->insertInvitation($invitationId, $teamId, 'alice@example.com', 'pending');
        $this->createAuthUser(email: 'intruder@example.com', teamId: Uuid::v7()->toRfc4122());

        self::createClient()->request('DELETE', \sprintf('/api/team-invitations/%s', $invitationId->toRfc4122()), [
            'headers' => $this->authHeaders('intruder@example.com'),
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_should_return422_when_invitation_already_cancelled(): void
    {
        $invitationId = Uuid::v7();
        $teamId = Uuid::v7();
        $headers = $this->memberOf($teamId);
        $this->insertInvitation($invitationId, $teamId, 'bob@example.com', 'cancelled');

        self::createClient()->request('DELETE', \sprintf('/api/team-invitations/%s', $invitationId->toRfc4122()), [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    public function test_should_return422_when_invitation_does_not_exist(): void
    {
        $invitationId = Uuid::v7();
        $headers = $this->memberOf(Uuid::v7());

        self::createClient()->request('DELETE', \sprintf('/api/team-invitations/%s', $invitationId->toRfc4122()), [
            'headers' => $headers,
        ]);

        self::assertResponseStatusCodeSame(422);
    }
}
