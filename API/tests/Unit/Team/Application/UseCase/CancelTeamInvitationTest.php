<?php

declare(strict_types=1);

namespace App\Tests\Unit\Team\Application\UseCase;

use App\Team\Application\UseCase\CancelTeamInvitation;
use App\Team\Domain\Command\CancelTeamInvitationCommand;
use App\Team\Domain\Entity\InvitationStatus;
use App\Team\Domain\Entity\TeamInvitation;
use App\Team\Domain\Event\CoHostInvitationCancelled;
use App\Team\Domain\Exception\InvalidInvitationException;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use App\Tests\Unit\Team\Infrastructure\InMemoryTeamInvitationRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CancelTeamInvitationTest extends TestCase
{
    private InMemoryTeamInvitationRepository $repository;
    private InMemoryEventBus $eventBus;
    private CancelTeamInvitation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryTeamInvitationRepository();
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new CancelTeamInvitation($this->repository, $this->eventBus);
    }

    public function testShouldCancelPendingInvitationAndDispatchEvent(): void
    {
        $invitationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');

        $invitation = new TeamInvitation(
            id: $invitationId,
            teamId: $teamId,
            email: 'alice@example.com',
            status: InvitationStatus::Pending,
            createdAt: new \DateTimeImmutable(),
        );
        $this->repository->save($invitation);

        $this->useCase->handle(new CancelTeamInvitationCommand(invitationId: $invitationId));

        $saved = $this->repository->findById($invitationId);
        self::assertNotNull($saved);
        self::assertSame(InvitationStatus::Cancelled, $saved->getStatus());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CoHostInvitationCancelled::class, $events[0]);
        self::assertTrue($invitationId->equals($events[0]->invitationId));
    }

    public function testShouldThrowWhenInvitationNotFound(): void
    {
        $this->expectException(InvalidInvitationException::class);
        $this->expectExceptionMessage('was not found');

        $this->useCase->handle(new CancelTeamInvitationCommand(
            invitationId: Uuid::fromString('01961e2f-dead-7000-beef-000000000099'),
        ));
    }

    public function testShouldThrowWhenInvitationAlreadyFinalized(): void
    {
        $invitationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');

        $invitation = new TeamInvitation(
            id: $invitationId,
            teamId: $teamId,
            email: 'alice@example.com',
            status: InvitationStatus::Pending,
            createdAt: new \DateTimeImmutable(),
        );
        $invitation->cancel();
        $this->repository->save($invitation);

        $this->expectException(InvalidInvitationException::class);
        $this->expectExceptionMessage('already finalized');

        $this->useCase->handle(new CancelTeamInvitationCommand(invitationId: $invitationId));
    }
}
