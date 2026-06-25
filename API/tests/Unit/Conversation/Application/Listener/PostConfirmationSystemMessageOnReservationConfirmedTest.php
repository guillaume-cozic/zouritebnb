<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Application\Listener;

use App\Conversation\Application\Listener\PostConfirmationSystemMessageOnReservationConfirmed;
use App\Conversation\Application\UseCase\PostSystemMessage;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Shared\Domain\Event\MessagePosted;
use App\Shared\Domain\Event\ReservationConfirmed;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryConversationRepository;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PostConfirmationSystemMessageOnReservationConfirmedTest extends TestCase
{
    private InMemoryConversationRepository $repository;
    private FixedClock $clock;
    private InMemoryEventBus $eventBus;
    private PostConfirmationSystemMessageOnReservationConfirmed $listener;
    private Uuid $conversationId;
    private Uuid $reservationId;

    #[Before]
    public function initListener(): void
    {
        $this->repository = new InMemoryConversationRepository();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-05-14T10:00:00+00:00'));
        $this->eventBus = new InMemoryEventBus();
        $useCase = new PostSystemMessage($this->repository, $this->clock, $this->eventBus);
        $this->listener = new PostConfirmationSystemMessageOnReservationConfirmed($useCase);

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

    public function test_should_post_confirmation_system_message(): void
    {
        UuidGenerator::queue([Uuid::v7()]);

        ($this->listener)(new ReservationConfirmed(reservationId: $this->reservationId));

        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        self::assertCount(1, $conversation->getMessages());

        $message = $conversation->getMessages()[0];
        self::assertTrue($message->isSystem());
        self::assertStringContainsString("L'hôte a accepté votre réservation.", $message->getBody()->toString());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MessagePosted::class, $events[0]);
        self::assertTrue($events[0]->isSystem);
    }

    public function test_should_silently_skip_when_no_conversation_for_reservation(): void
    {
        ($this->listener)(new ReservationConfirmed(
            reservationId: Uuid::fromString('01961e2f-dead-7000-beef-0000000000ee'),
        ));

        $conversation = $this->repository->ofId(new ConversationId($this->conversationId));
        self::assertCount(0, $conversation->getMessages());
        self::assertCount(0, $this->eventBus->getDispatchedEvents());
    }
}
