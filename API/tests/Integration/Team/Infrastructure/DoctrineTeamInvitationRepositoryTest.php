<?php

declare(strict_types=1);

namespace App\Tests\Integration\Team\Infrastructure;

use App\Team\Domain\Entity\InvitationStatus;
use App\Team\Domain\Entity\TeamInvitation;
use App\Team\Domain\Port\TeamInvitationRepository;
use App\Tests\Integration\RepositoryTestCase;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\Uid\Uuid;

final class DoctrineTeamInvitationRepositoryTest extends RepositoryTestCase
{
    private TeamInvitationRepository $repository;

    #[Before]
    public function initRepository(): void
    {
        $this->repository = self::getContainer()->get(TeamInvitationRepository::class);
    }

    public function test_should_save_and_find_by_id(): void
    {
        $id = Uuid::v4();
        $teamId = Uuid::v4();
        $createdAt = new \DateTimeImmutable('2026-01-15 10:00:00');
        $invitation = new TeamInvitation(
            id: $id,
            teamId: $teamId,
            email: 'invitee@example.com',
            status: InvitationStatus::Pending,
            createdAt: $createdAt,
        );

        $this->repository->save($invitation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertEquals($id, $found->getId());
        self::assertEquals($teamId, $found->getTeamId());
        self::assertSame('invitee@example.com', $found->getEmail());
        self::assertSame(InvitationStatus::Pending, $found->getStatus());
        self::assertEquals($createdAt, $found->getCreatedAt());
    }

    public function test_should_return_null_when_not_found(): void
    {
        $result = $this->repository->findById(Uuid::v4());

        self::assertNull($result);
    }

    public function test_should_update_existing_entity(): void
    {
        $id = Uuid::v4();
        $teamId = Uuid::v4();
        $invitation = new TeamInvitation(
            id: $id,
            teamId: $teamId,
            email: 'old@example.com',
            status: InvitationStatus::Pending,
            createdAt: new \DateTimeImmutable('2026-01-15 10:00:00'),
        );
        $this->repository->save($invitation);

        $updated = new TeamInvitation(
            id: $id,
            teamId: $teamId,
            email: 'new@example.com',
            status: InvitationStatus::Cancelled,
            createdAt: new \DateTimeImmutable('2026-02-20 12:00:00'),
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);
        self::assertSame('new@example.com', $found->getEmail());
        self::assertSame(InvitationStatus::Cancelled, $found->getStatus());
        self::assertEquals(new \DateTimeImmutable('2026-02-20 12:00:00'), $found->getCreatedAt());
    }

    public function test_should_persist_accepted_status(): void
    {
        $id = Uuid::v4();
        $invitation = new TeamInvitation(
            id: $id,
            teamId: Uuid::v4(),
            email: 'accepted@example.com',
            status: InvitationStatus::Accepted,
            createdAt: new \DateTimeImmutable('2026-03-01 09:00:00'),
        );

        $this->repository->save($invitation);
        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame(InvitationStatus::Accepted, $found->getStatus());
    }

    public function test_should_find_pending_invitations_by_team(): void
    {
        $teamId = Uuid::v4();
        $otherTeamId = Uuid::v4();

        $pendingOne = new TeamInvitation(
            id: Uuid::v4(),
            teamId: $teamId,
            email: 'pending-one@example.com',
            status: InvitationStatus::Pending,
            createdAt: new \DateTimeImmutable('2026-01-01 08:00:00'),
        );
        $pendingTwo = new TeamInvitation(
            id: Uuid::v4(),
            teamId: $teamId,
            email: 'pending-two@example.com',
            status: InvitationStatus::Pending,
            createdAt: new \DateTimeImmutable('2026-01-02 08:00:00'),
        );
        $accepted = new TeamInvitation(
            id: Uuid::v4(),
            teamId: $teamId,
            email: 'accepted@example.com',
            status: InvitationStatus::Accepted,
            createdAt: new \DateTimeImmutable('2026-01-03 08:00:00'),
        );
        $cancelled = new TeamInvitation(
            id: Uuid::v4(),
            teamId: $teamId,
            email: 'cancelled@example.com',
            status: InvitationStatus::Cancelled,
            createdAt: new \DateTimeImmutable('2026-01-04 08:00:00'),
        );
        $otherTeamPending = new TeamInvitation(
            id: Uuid::v4(),
            teamId: $otherTeamId,
            email: 'other-team@example.com',
            status: InvitationStatus::Pending,
            createdAt: new \DateTimeImmutable('2026-01-05 08:00:00'),
        );

        $this->repository->save($pendingOne);
        $this->repository->save($pendingTwo);
        $this->repository->save($accepted);
        $this->repository->save($cancelled);
        $this->repository->save($otherTeamPending);

        $result = $this->repository->findPendingByTeam($teamId);

        self::assertCount(2, $result);
        $emails = array_map(static fn (TeamInvitation $invitation): string => $invitation->getEmail(), $result);
        self::assertSame(['pending-one@example.com', 'pending-two@example.com'], $emails);
        foreach ($result as $invitation) {
            self::assertSame(InvitationStatus::Pending, $invitation->getStatus());
            self::assertEquals($teamId, $invitation->getTeamId());
        }
    }

    public function test_should_return_empty_array_when_no_pending_invitation_for_team(): void
    {
        $teamId = Uuid::v4();
        $accepted = new TeamInvitation(
            id: Uuid::v4(),
            teamId: $teamId,
            email: 'accepted@example.com',
            status: InvitationStatus::Accepted,
            createdAt: new \DateTimeImmutable('2026-01-01 08:00:00'),
        );
        $this->repository->save($accepted);

        $result = $this->repository->findPendingByTeam($teamId);

        self::assertSame([], $result);
    }

    public function test_should_return_empty_array_when_team_has_no_invitation(): void
    {
        $result = $this->repository->findPendingByTeam(Uuid::v4());

        self::assertSame([], $result);
    }
}
