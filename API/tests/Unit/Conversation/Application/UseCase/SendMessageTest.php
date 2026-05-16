<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Application\UseCase;

use App\Conversation\Application\UseCase\SendMessage;
use App\Conversation\Domain\Command\SendMessageCommand;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Exception\ConversationNotFoundException;
use App\Conversation\Domain\Exception\ConversationParticipantException;
use App\Conversation\Domain\Exception\InvalidMessageException;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryConversationRepository;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryTeamMembershipChecker;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendMessageTest extends TestCase
{
    private InMemoryConversationRepository $repository;
    private InMemoryTeamMembershipChecker $teamMembershipChecker;
    private FixedClock $clock;
    private InMemoryEventBus $eventBus;
    private SendMessage $useCase;
    private Uuid $conversationId;
    private Uuid $teamId;
    private Uuid $guestUserId;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryConversationRepository();
        $this->teamMembershipChecker = new InMemoryTeamMembershipChecker();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-05-14T10:00:00+00:00'));
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new SendMessage($this->repository, $this->teamMembershipChecker, $this->clock, $this->eventBus);

        $this->conversationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $this->teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');
        $this->guestUserId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1');

        $this->repository->save(new Conversation(
            id: new ConversationId($this->conversationId),
            reservationId: Uuid::v7(),
            accommodationId: Uuid::v7(),
            teamId: $this->teamId,
            guestUserId: $this->guestUserId,
            createdAt: new \DateTimeImmutable('2026-05-14T09:00:00+00:00'),
        ));
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function testShouldLetGuestPostMessage(): void
    {
        $this->useCase->handle(new SendMessageCommand(
            conversationId: $this->conversationId->toRfc4122(),
            authorUserId: $this->guestUserId->toRfc4122(),
            body: 'Bonjour',
        ));

        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        self::assertCount(1, $conversation->getMessages());
        $message = $conversation->getMessages()[0];
        self::assertFalse($message->isSystem());
        self::assertSame('Bonjour', $message->getBody()->toString());
        self::assertTrue($this->guestUserId->equals($message->getAuthorUserId()));
    }

    public function testShouldLetTeamMemberPostMessage(): void
    {
        $teamMember = Uuid::fromString('01961e2f-dead-7000-beef-0000000000d1');
        $this->teamMembershipChecker->add($teamMember, $this->teamId);

        $this->useCase->handle(new SendMessageCommand(
            conversationId: $this->conversationId->toRfc4122(),
            authorUserId: $teamMember->toRfc4122(),
            body: 'Bienvenue !',
        ));

        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        self::assertCount(1, $conversation->getMessages());
        $message = $conversation->getMessages()[0];
        self::assertTrue($teamMember->equals($message->getAuthorUserId()));
    }

    public function testShouldRejectOutsider(): void
    {
        $outsider = Uuid::v7();

        $this->expectException(ConversationParticipantException::class);

        $this->useCase->handle(new SendMessageCommand(
            conversationId: $this->conversationId->toRfc4122(),
            authorUserId: $outsider->toRfc4122(),
            body: 'Hi',
        ));
    }

    public function testShouldThrowNotFoundForUnknownConversation(): void
    {
        $this->expectException(ConversationNotFoundException::class);

        $this->useCase->handle(new SendMessageCommand(
            conversationId: Uuid::v7()->toRfc4122(),
            authorUserId: $this->guestUserId->toRfc4122(),
            body: 'Hi',
        ));
    }

    public function testShouldRejectEmptyBody(): void
    {
        $this->expectException(InvalidMessageException::class);

        $this->useCase->handle(new SendMessageCommand(
            conversationId: $this->conversationId->toRfc4122(),
            authorUserId: $this->guestUserId->toRfc4122(),
            body: '   ',
        ));
    }
}
