<?php

declare(strict_types=1);

namespace App\Conversation\Application\UseCase;

use App\Conversation\Domain\Command\SendMessageCommand;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Entity\MessageBody;
use App\Conversation\Domain\Entity\MessageId;
use App\Conversation\Domain\Exception\ConversationNotFoundException;
use App\Conversation\Domain\Exception\ConversationParticipantException;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\TeamMembershipChecker;
use App\Shared\Domain\Port\UuidGenerator;
use Symfony\Component\Uid\Uuid;

final readonly class SendMessage
{
    public function __construct(
        private ConversationRepository $repository,
        private TeamMembershipChecker $teamMembershipChecker,
        private Clock $clock,
        private EventBus $eventBus,
    ) {
    }

    public function handle(SendMessageCommand $command): string
    {
        $conversationId = new ConversationId(Uuid::fromString($command->conversationId));
        $authorUserId = Uuid::fromString($command->authorUserId);

        $conversation = $this->repository->ofId($conversationId);
        if (null === $conversation) {
            throw ConversationNotFoundException::becauseId($command->conversationId);
        }

        $now = $this->clock->now();
        $body = new MessageBody($command->body);
        $messageId = new MessageId(UuidGenerator::generate());

        if ($conversation->isGuest($authorUserId)) {
            $message = $conversation->postGuestMessage($messageId, $body, $now);
        } elseif ($this->teamMembershipChecker->isMember($authorUserId, $conversation->getTeamId())) {
            $message = $conversation->postHostMessage($messageId, $body, $authorUserId, $now);
        } else {
            throw ConversationParticipantException::becauseUserIsNotAllowed($command->authorUserId);
        }

        $this->repository->save($conversation);
        $this->eventBus->dispatch($conversation->releaseEvents());

        return $message->getId()->toString();
    }
}
