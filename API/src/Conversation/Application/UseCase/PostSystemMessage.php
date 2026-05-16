<?php

declare(strict_types=1);

namespace App\Conversation\Application\UseCase;

use App\Conversation\Domain\Command\PostSystemMessageCommand;
use App\Conversation\Domain\Entity\MessageBody;
use App\Conversation\Domain\Entity\MessageId;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\UuidGenerator;

final readonly class PostSystemMessage
{
    public function __construct(
        private ConversationRepository $repository,
        private Clock $clock,
        private EventBus $eventBus,
    ) {
    }

    public function handle(PostSystemMessageCommand $command): void
    {
        $conversation = $this->repository->ofReservationId($command->reservationId);
        if (null === $conversation) {
            // No conversation tied to this reservation (e.g. back-office direct insert) — silently skip.
            return;
        }

        $conversation->postSystemMessage(
            messageId: new MessageId(UuidGenerator::generate()),
            body: new MessageBody($command->body),
            sentAt: $this->clock->now(),
        );

        $this->repository->save($conversation);
        $this->eventBus->dispatch($conversation->releaseEvents());
    }
}
