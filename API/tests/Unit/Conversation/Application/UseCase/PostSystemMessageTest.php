<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Application\UseCase;

use App\Conversation\Application\UseCase\PostSystemMessage;
use App\Conversation\Domain\Command\PostSystemMessageCommand;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Shared\Domain\Event\MessagePosted;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryConversationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PostSystemMessageTest extends TestCase
{
    private InMemoryConversationRepository $repository;
    private FixedClock $clock;
    private InMemoryEventBus $eventBus;
    private PostSystemMessage $useCase;
    private Uuid $conversationId;
    private Uuid $reservationId;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryConversationRepository();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-05-14T10:00:00+00:00'));
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new PostSystemMessage($this->repository, $this->clock, $this->eventBus);

        $this->conversationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $this->reservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');

        $this->repository->save(new Conversation(
            id: new ConversationId($this->conversationId),
            reservationId: $this->reservationId,
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            guestUserId: Uuid::v7(),
            createdAt: new \DateTimeImmutable('2026-05-14T09:00:00+00:00'),
        ));
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_post_system_message_on_conversation_of_reservation(): void
    {
        $messageId = Uuid::fromString('01961e2f-dead-7000-beef-000000000020');
        UuidGenerator::queue([$messageId]);

        $this->useCase->handle(new PostSystemMessageCommand(
            reservationId: $this->reservationId,
            body: 'Message système.',
        ));

        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        self::assertCount(1, $conversation->getMessages());

        $message = $conversation->getMessages()[0];
        self::assertTrue($message->isSystem());
        self::assertNull($message->getAuthorUserId());
        self::assertSame('Message système.', $message->getBody()->toString());
        self::assertEquals($this->clock->now(), $message->getSentAt());
    }

    public function test_should_dispatch_message_posted_event(): void
    {
        $messageId = Uuid::fromString('01961e2f-dead-7000-beef-000000000020');
        UuidGenerator::queue([$messageId]);

        $this->useCase->handle(new PostSystemMessageCommand(
            reservationId: $this->reservationId,
            body: 'Message système.',
        ));

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MessagePosted::class, $events[0]);
        self::assertTrue($this->conversationId->equals($events[0]->conversationId));
        self::assertTrue($messageId->equals($events[0]->messageId));
        self::assertTrue($events[0]->isSystem);
    }

    public function test_should_silently_skip_when_no_conversation_for_reservation(): void
    {
        $unknownReservationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000ee');

        $this->useCase->handle(new PostSystemMessageCommand(
            reservationId: $unknownReservationId,
            body: 'Message système.',
        ));

        // Existing conversation is untouched and no events are dispatched.
        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        self::assertCount(0, $conversation->getMessages());
        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }
}
