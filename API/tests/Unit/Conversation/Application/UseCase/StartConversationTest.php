<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Application\UseCase;

use App\Conversation\Application\UseCase\StartConversation;
use App\Conversation\Domain\Command\StartConversationCommand;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Event\ConversationStarted;
use App\Conversation\Domain\Exception\CannotStartConversationException;
use App\Shared\Domain\Port\ReservationSummary;
use App\Shared\Domain\Port\UuidGenerator;
use App\Tests\Unit\Conversation\Infrastructure\FixedClock;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryConversationRepository;
use App\Tests\Unit\Conversation\Infrastructure\InMemoryReservationSummaryProvider;
use App\Tests\Unit\Shared\Infrastructure\InMemoryEventBus;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class StartConversationTest extends TestCase
{
    private InMemoryConversationRepository $repository;
    private InMemoryReservationSummaryProvider $reservationProvider;
    private FixedClock $clock;
    private InMemoryEventBus $eventBus;
    private StartConversation $useCase;

    #[Before]
    public function initUseCase(): void
    {
        $this->repository = new InMemoryConversationRepository();
        $this->reservationProvider = new InMemoryReservationSummaryProvider();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-05-14T10:00:00+00:00'));
        $this->eventBus = new InMemoryEventBus();
        $this->useCase = new StartConversation($this->repository, $this->reservationProvider, $this->clock, $this->eventBus);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function testShouldStartConversationWithSystemMessageContainingTemplate(): void
    {
        $reservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $accommodationId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000a1');
        $teamId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000b1');
        $guestUserId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1');
        $conversationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000010');
        $messageId = Uuid::fromString('01961e2f-dead-7000-beef-000000000011');

        UuidGenerator::queue([$conversationId, $messageId]);

        $this->reservationProvider->add(new ReservationSummary(
            reservationId: $reservationId,
            accommodationId: $accommodationId,
            teamId: $teamId,
            guestUserId: $guestUserId,
            guestName: 'Jean Dupont',
            checkIn: new \DateTimeImmutable('2026-06-10'),
            checkOut: new \DateTimeImmutable('2026-06-15'),
        ));

        $returnedId = $this->useCase->handle(new StartConversationCommand(reservationId: $reservationId));

        self::assertSame($conversationId->toRfc4122(), $returnedId);

        $conversation = $this->repository->ofId(new ConversationId($conversationId));
        self::assertNotNull($conversation);
        self::assertCount(1, $conversation->getMessages());

        $message = $conversation->getMessages()[0];
        self::assertTrue($message->isSystem());
        self::assertNull($message->getAuthorUserId());
        self::assertStringContainsString('Jean Dupont', $message->getBody()->toString());
        self::assertStringContainsString('10/06/2026', $message->getBody()->toString());
        self::assertStringContainsString('15/06/2026', $message->getBody()->toString());

        $events = $this->eventBus->getDispatchedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ConversationStarted::class, $events[0]);
        self::assertTrue($conversationId->equals($events[0]->conversationId));
        self::assertTrue($messageId->equals($events[0]->openingMessageId));
    }

    public function testShouldAppendGuestNoteToOpeningMessage(): void
    {
        $reservationId = Uuid::v7();
        UuidGenerator::queue([Uuid::v7(), Uuid::v7()]);

        $this->reservationProvider->add(new ReservationSummary(
            reservationId: $reservationId,
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            guestUserId: Uuid::v7(),
            guestName: 'Marie',
            checkIn: new \DateTimeImmutable('2026-07-01'),
            checkOut: new \DateTimeImmutable('2026-07-05'),
        ));

        $this->useCase->handle(new StartConversationCommand(
            reservationId: $reservationId,
            note: 'Nous voyageons avec un bébé.',
        ));

        $conversation = $this->repository->ofReservationId($reservationId);
        self::assertNotNull($conversation);
        $body = $conversation->getMessages()[0]->getBody()->toString();
        self::assertStringContainsString('Nous voyageons avec un bébé.', $body);
    }

    public function testShouldBeIdempotentWhenReservationAlreadyHasConversation(): void
    {
        $reservationId = Uuid::v7();
        UuidGenerator::queue([Uuid::v7(), Uuid::v7()]);

        $this->reservationProvider->add(new ReservationSummary(
            reservationId: $reservationId,
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            guestUserId: Uuid::v7(),
            guestName: 'Marie',
            checkIn: new \DateTimeImmutable('2026-07-01'),
            checkOut: new \DateTimeImmutable('2026-07-05'),
        ));

        $first = $this->useCase->handle(new StartConversationCommand(reservationId: $reservationId));
        $second = $this->useCase->handle(new StartConversationCommand(reservationId: $reservationId));

        self::assertSame($first, $second);
        self::assertCount(1, $this->repository->listForGuestUser($this->repository->ofReservationId($reservationId)->getGuestUserId()));
    }

    public function testShouldThrowWhenReservationNotFound(): void
    {
        $this->expectException(CannotStartConversationException::class);

        $this->useCase->handle(new StartConversationCommand(reservationId: Uuid::v7()));
    }

    public function testShouldThrowWhenReservationHasNoGuestUser(): void
    {
        $reservationId = Uuid::v7();
        $this->reservationProvider->add(new ReservationSummary(
            reservationId: $reservationId,
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            guestUserId: null,
            guestName: 'Anon',
            checkIn: new \DateTimeImmutable('2026-07-01'),
            checkOut: new \DateTimeImmutable('2026-07-05'),
        ));

        $this->expectException(CannotStartConversationException::class);

        $this->useCase->handle(new StartConversationCommand(reservationId: $reservationId));
    }
}
