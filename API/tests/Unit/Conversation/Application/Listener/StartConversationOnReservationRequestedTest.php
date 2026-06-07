<?php

declare(strict_types=1);

namespace App\Tests\Unit\Conversation\Application\Listener;

use App\Conversation\Application\Listener\StartConversationOnReservationRequested;
use App\Conversation\Application\UseCase\StartConversation;
use App\Shared\Domain\Event\ReservationRequested;
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

final class StartConversationOnReservationRequestedTest extends TestCase
{
    private InMemoryConversationRepository $repository;
    private InMemoryReservationSummaryProvider $reservationProvider;
    private FixedClock $clock;
    private InMemoryEventBus $eventBus;
    private StartConversationOnReservationRequested $listener;

    #[Before]
    public function initListener(): void
    {
        $this->repository = new InMemoryConversationRepository();
        $this->reservationProvider = new InMemoryReservationSummaryProvider();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-05-14T10:00:00+00:00'));
        $this->eventBus = new InMemoryEventBus();
        $useCase = new StartConversation($this->repository, $this->reservationProvider, $this->clock, $this->eventBus);
        $this->listener = new StartConversationOnReservationRequested($useCase);
    }

    #[After]
    public function resetUuid(): void
    {
        UuidGenerator::reset();
    }

    public function test_should_start_conversation_from_reservation_requested_event(): void
    {
        $reservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000001');
        $guestUserId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c1');
        UuidGenerator::queue([Uuid::v7(), Uuid::v7()]);

        $this->reservationProvider->add(new ReservationSummary(
            reservationId: $reservationId,
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            guestUserId: $guestUserId,
            guestName: 'Jean Dupont',
            checkIn: new \DateTimeImmutable('2026-06-10'),
            checkOut: new \DateTimeImmutable('2026-06-15'),
        ));

        ($this->listener)(new ReservationRequested(
            reservationId: $reservationId,
            guestUserId: $guestUserId,
        ));

        $conversation = $this->repository->ofReservationId($reservationId);
        self::assertNotNull($conversation);
        self::assertCount(1, $conversation->getMessages());
        self::assertStringContainsString('Jean Dupont', $conversation->getMessages()[0]->getBody()->toString());
    }

    public function test_should_forward_note_to_opening_message(): void
    {
        $reservationId = Uuid::fromString('01961e2f-dead-7000-beef-000000000002');
        $guestUserId = Uuid::fromString('01961e2f-dead-7000-beef-0000000000c2');
        UuidGenerator::queue([Uuid::v7(), Uuid::v7()]);

        $this->reservationProvider->add(new ReservationSummary(
            reservationId: $reservationId,
            accommodationId: Uuid::v7(),
            teamId: Uuid::v7(),
            guestUserId: $guestUserId,
            guestName: 'Marie',
            checkIn: new \DateTimeImmutable('2026-07-01'),
            checkOut: new \DateTimeImmutable('2026-07-05'),
        ));

        ($this->listener)(new ReservationRequested(
            reservationId: $reservationId,
            guestUserId: $guestUserId,
            note: 'Nous voyageons avec un bébé.',
        ));

        $conversation = $this->repository->ofReservationId($reservationId);
        self::assertNotNull($conversation);
        self::assertStringContainsString('Nous voyageons avec un bébé.', $conversation->getMessages()[0]->getBody()->toString());
    }
}
