<?php

declare(strict_types=1);

namespace App\Conversation\Application\UseCase;

use App\Conversation\Domain\Command\StartConversationCommand;
use App\Conversation\Domain\Entity\Conversation;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Entity\MessageBody;
use App\Conversation\Domain\Entity\MessageId;
use App\Conversation\Domain\Exception\CannotStartConversationException;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\ReservationSummaryProvider;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class StartConversation
{
    public function __construct(
        private ConversationRepository $repository,
        private ReservationSummaryProvider $reservationProvider,
        private Clock $clock,
        private EventBus $eventBus,
    ) {
    }

    public function handle(StartConversationCommand $command): string
    {
        $existing = $this->repository->ofReservationId($command->reservationId);
        if (null !== $existing) {
            // Idempotent: re-firing the listener should not duplicate the conversation.
            return $existing->getId()->toString();
        }

        $summary = $this->reservationProvider->findById($command->reservationId);
        if (null === $summary) {
            throw CannotStartConversationException::becauseReservationNotFound($command->reservationId->toRfc4122());
        }
        if (null === $summary->guestUserId) {
            throw CannotStartConversationException::becauseReservationHasNoGuestUser($command->reservationId->toRfc4122());
        }

        $now = $this->clock->now();

        $conversation = Conversation::start(
            id: new ConversationId(UuidGenerator::generate()),
            reservationId: $summary->reservationId,
            accommodationId: $summary->accommodationId,
            teamId: $summary->teamId,
            guestUserId: $summary->guestUserId,
            createdAt: $now,
        );

        $conversation->postSystemMessage(
            messageId: new MessageId(UuidGenerator::generate()),
            body: new MessageBody($this->buildOpeningMessage($summary->checkIn, $summary->checkOut, $summary->guestName, $command->note)),
            sentAt: $now,
        );

        $this->repository->save($conversation);
        $this->eventBus->dispatch($conversation->releaseEvents());

        return $conversation->getId()->toString();
    }

    private function buildOpeningMessage(
        \DateTimeImmutable $checkIn,
        \DateTimeImmutable $checkOut,
        string $guestName,
        ?string $note,
    ): string {
        $template = \sprintf(
            "Bonjour, je m'appelle %s et je souhaite réserver votre hébergement du %s au %s.",
            $guestName,
            $checkIn->format('d/m/Y'),
            $checkOut->format('d/m/Y'),
        );

        $trimmedNote = null !== $note ? trim($note) : '';
        if ('' !== $trimmedNote) {
            $template .= "\n\n".$trimmedNote;
        }

        return $template;
    }
}
