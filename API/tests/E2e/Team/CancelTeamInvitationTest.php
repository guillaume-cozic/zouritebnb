<?php

declare(strict_types=1);

namespace App\Tests\E2e\Team;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Team\Infrastructure\Doctrine\TeamInvitationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class CancelTeamInvitationTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    public function testShouldCancelPendingInvitationAndReturn204(): void
    {
        $invitationId = Uuid::v7();
        $teamId = Uuid::v7();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new TeamInvitationEntity()
            ->setId($invitationId)
            ->setTeamId($teamId)
            ->setEmail('alice@example.com')
            ->setStatus('pending')
            ->setCreatedAt(new \DateTimeImmutable());

        $em->persist($entity);
        $em->flush();

        self::createClient()->request('DELETE', \sprintf('/api/team-invitations/%s', $invitationId->toRfc4122()));

        self::assertResponseStatusCodeSame(204);
    }

    public function testShouldReturn422WhenInvitationAlreadyCancelled(): void
    {
        $invitationId = Uuid::v7();
        $teamId = Uuid::v7();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        $entity = new TeamInvitationEntity()
            ->setId($invitationId)
            ->setTeamId($teamId)
            ->setEmail('bob@example.com')
            ->setStatus('cancelled')
            ->setCreatedAt(new \DateTimeImmutable());

        $em->persist($entity);
        $em->flush();

        self::createClient()->request('DELETE', \sprintf('/api/team-invitations/%s', $invitationId->toRfc4122()));

        self::assertResponseStatusCodeSame(422);
    }

    public function testShouldReturn422WhenInvitationDoesNotExist(): void
    {
        $invitationId = Uuid::v7();

        self::createClient()->request('DELETE', \sprintf('/api/team-invitations/%s', $invitationId->toRfc4122()));

        self::assertResponseStatusCodeSame(422);
    }
}
