<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Application\UseCase;

use App\Shared\Domain\Port\UuidGenerator;
use App\Team\Application\UseCase\InviteCoHost;
use App\Team\Domain\Command\InviteCoHostCommand;
use App\Team\Domain\Entity\InvitationStatus;
use App\Team\Domain\Event\CoHostInvited;
use App\Team\Domain\Exception\InvalidInvitationException;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use App\Tests\Unit\Team\Infrastructure\InMemoryTeamInvitationRepository;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class InviteCoHostTest extends TestCase
{
    private InMemoryTeamInvitationRepository $repository;
    private InMemoryEventBus $eventBus;
    private InviteCoHost $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryTeamInvitationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new InviteCoHost($this->repository, $this->eventBus);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_create_invitation_and_dispatch_event(): void
    {
        $invitationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');
        UuidGenerator::freeze($invitationId);

        $id = $this->useCase->handle(new InviteCoHostCommand(
            teamId: $teamId,
            email: 'alice@example.com',
        ));

        self::assertSame($invitationId->toRfc4122(), $id);
        $invitation = $this->repository->findById($invitationId);
        self::assertNotNull($invitation);
        self::assertSame('alice@example.com', $invitation->getEmail());
        self::assertSame(InvitationStatus::Pending, $invitation->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CoHostInvited::class, $events[0]);
        self::assertTrue($invitationId->equals($events[0]->invitationId));
        self::assertTrue($teamId->equals($events[0]->teamId));
        self::assertSame('alice@example.com', $events[0]->email);
    }

    public function test_should_reject_empty_email(): void
    {
        $this->expectException(InvalidInvitationException::class);
        $this->expectExceptionMessage('Invitation email must not be empty.');

        $this->useCase->handle(new InviteCoHostCommand(
            teamId: Uuid::v7(),
            email: '   ',
        ));
    }

    public function test_should_reject_invalid_email_format(): void
    {
        $this->expectException(InvalidInvitationException::class);
        $this->expectExceptionMessage('is not a valid email address');

        $this->useCase->handle(new InviteCoHostCommand(
            teamId: Uuid::v7(),
            email: 'not-an-email',
        ));
    }

    public function test_should_reject_duplicate_pending_invitation(): void
    {
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');

        UuidGenerator::freeze(Uuid::fromString('01961e2f-dead-7000-beef-000000000001'));
        $this->useCase->handle(new InviteCoHostCommand(teamId: $teamId, email: 'alice@example.com'));
        UuidGenerator::reset();

        $this->expectException(InvalidInvitationException::class);
        $this->expectExceptionMessage('pending invitation already exists');

        $this->useCase->handle(new InviteCoHostCommand(teamId: $teamId, email: 'ALICE@example.com'));
    }
}
