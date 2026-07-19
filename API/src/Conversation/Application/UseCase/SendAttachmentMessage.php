<?php

declare(strict_types=1);

namespace App\Conversation\Application\UseCase;

use App\Conversation\Domain\Command\SendAttachmentMessageCommand;
use App\Conversation\Domain\Entity\ConversationId;
use App\Conversation\Domain\Entity\MessageAttachment;
use App\Conversation\Domain\Entity\MessageBody;
use App\Conversation\Domain\Entity\MessageId;
use App\Conversation\Domain\Exception\ConversationNotFoundException;
use App\Conversation\Domain\Exception\ConversationParticipantException;
use App\Conversation\Domain\Exception\InvalidAttachmentException;
use App\Conversation\Domain\Port\AttachmentImageTransformer;
use App\Conversation\Domain\Port\AttachmentStorage;
use App\Conversation\Domain\Port\ConversationRepository;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\EventBus;
use App\Shared\Domain\Port\TeamMembershipChecker;
use App\Shared\Domain\Port\UuidGenerator;
use Symfony\Component\Uid\Uuid;

final readonly class SendAttachmentMessage
{
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Hard cap on the uploaded image size (10 MB) to prevent memory-exhaustion DoS. */
    private const int MAX_SIZE_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private ConversationRepository $repository,
        private TeamMembershipChecker $teamMembershipChecker,
        private AttachmentImageTransformer $imageTransformer,
        private AttachmentStorage $attachmentStorage,
        private Clock $clock,
        private EventBus $eventBus,
    ) {
    }

    public function handle(SendAttachmentMessageCommand $command): string
    {
        if (!\in_array($command->mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw InvalidAttachmentException::becauseInvalidMimeType($command->mimeType);
        }

        if ($command->size > self::MAX_SIZE_BYTES) {
            throw InvalidAttachmentException::becauseTooLarge($command->size, self::MAX_SIZE_BYTES);
        }

        $conversationId = new ConversationId(Uuid::fromString($command->conversationId));
        $authorUserId = Uuid::fromString($command->authorUserId);

        $conversation = $this->repository->ofId($conversationId);
        if (null === $conversation) {
            throw ConversationNotFoundException::becauseId($command->conversationId);
        }

        $isGuest = $conversation->isGuest($authorUserId);
        if (!$isGuest && !$this->teamMembershipChecker->isMember($authorUserId, $conversation->getTeamId())) {
            throw ConversationParticipantException::becauseUserIsNotAllowed($command->authorUserId);
        }

        $now = $this->clock->now();
        $body = null !== $command->body && '' !== trim($command->body) ? new MessageBody($command->body) : null;
        $messageId = new MessageId(UuidGenerator::generate());

        $webpContent = $this->imageTransformer->toWebp($command->content);
        $attachment = new MessageAttachment(UuidGenerator::generate()->toRfc4122().'.webp');
        $this->attachmentStorage->store($attachment->filename(), $webpContent);

        $message = $isGuest
            ? $conversation->postGuestMessage($messageId, $body, $now, $attachment)
            : $conversation->postHostMessage($messageId, $body, $authorUserId, $now, $attachment);

        $this->repository->save($conversation);
        $this->eventBus->dispatch($conversation->releaseEvents());

        return $message->getId()->toString();
    }
}
