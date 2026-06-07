<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Domain\Entity;

use App\Team\Domain\Entity\InvitationStatus;
use App\Team\Domain\Entity\TeamInvitation;
use App\Team\Domain\Exception\InvalidInvitationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TeamInvitationTest extends TestCase
{
    public function test_should_create_a_valid_invitation(): void
    {
        $id = Uuid::v7();
        $teamId = Uuid::v7();
        $createdAt = new \DateTimeImmutable('2026-06-07T10:00:00+00:00');

        $invitation = new TeamInvitation(
            id: $id,
            teamId: $teamId,
            email: 'cohost@example.com',
            status: InvitationStatus::Pending,
            createdAt: $createdAt,
        );

        self::assertSame($id, $invitation->getId());
        self::assertSame($teamId, $invitation->getTeamId());
        self::assertSame('cohost@example.com', $invitation->getEmail());
        self::assertSame(InvitationStatus::Pending, $invitation->getStatus());
        self::assertSame($createdAt, $invitation->getCreatedAt());
    }

    public function test_should_throw_when_email_is_empty(): void
    {
        $this->expectException(InvalidInvitationException::class);
        $this->expectExceptionMessage('Invitation email must not be empty.');

        $this->invitation(email: '   ');
    }

    public function test_should_throw_when_email_format_is_invalid(): void
    {
        $this->expectException(InvalidInvitationException::class);
        $this->expectExceptionMessage('Invitation email "not-an-email" is not a valid email address.');

        $this->invitation(email: 'not-an-email');
    }

    public function test_should_cancel_a_pending_invitation(): void
    {
        $invitation = $this->invitation(status: InvitationStatus::Pending);

        $invitation->cancel();

        self::assertSame(InvitationStatus::Cancelled, $invitation->getStatus());
    }

    #[DataProvider('finalizedStatuses')]
    public function test_should_not_cancel_a_finalized_invitation(InvitationStatus $status): void
    {
        $invitation = $this->invitation(status: $status);

        $this->expectException(InvalidInvitationException::class);
        $this->expectExceptionMessage('Invitation is already finalized and cannot be cancelled.');

        $invitation->cancel();
    }

    public static function finalizedStatuses(): \Generator
    {
        yield 'accepted' => [InvitationStatus::Accepted];
        yield 'cancelled' => [InvitationStatus::Cancelled];
    }

    private function invitation(
        string $email = 'cohost@example.com',
        InvitationStatus $status = InvitationStatus::Pending,
    ): TeamInvitation {
        return new TeamInvitation(
            id: Uuid::v7(),
            teamId: Uuid::v7(),
            email: $email,
            status: $status,
            createdAt: new \DateTimeImmutable('2026-06-07T10:00:00+00:00'),
        );
    }
}
