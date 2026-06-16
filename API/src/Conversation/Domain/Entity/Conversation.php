<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Entity;

use App\Conversation\Domain\Event\ConversationStarted;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\Event\MessagePosted;
use Symfony\Component\Uid\Uuid;

final class Conversation extends AggregateRoot
{
    /**
     * @param Message[] $messages
     */
    public function __construct(
        private readonly ConversationId $id,
        private readonly Uuid $reservationId,
        private readonly Uuid $accommodationId,
        private readonly Uuid $teamId,
        private readonly Uuid $guestUserId,
        private readonly \DateTimeImmutable $createdAt,
        private array $messages = [],
    ) {
    }

    public static function start(
        ConversationId $id,
        Uuid $reservationId,
        Uuid $accommodationId,
        Uuid $teamId,
        Uuid $guestUserId,
        MessageId $openingMessageId,
        MessageBody $openingMessageBody,
        \DateTimeImmutable $createdAt,
    ): self {
        $conversation = new self($id, $reservationId, $accommodationId, $teamId, $guestUserId, $createdAt);
        $conversation->messages[] = Message::system($openingMessageId, $openingMessageBody, $createdAt);
        $conversation->recordEvent(new ConversationStarted($id->toUuid(), $reservationId, $openingMessageId->toUuid()));

        return $conversation;
    }

    public function postSystemMessage(MessageId $messageId, MessageBody $body, \DateTimeImmutable $sentAt): Message
    {
        $message = Message::system($messageId, $body, $sentAt);
        $this->messages[] = $message;
        $this->recordEvent(new MessagePosted($this->id->toUuid(), $messageId->toUuid(), true));

        return $message;
    }

    public function postGuestMessage(MessageId $messageId, MessageBody $body, \DateTimeImmutable $sentAt): Message
    {
        $message = Message::user($messageId, $body, $this->guestUserId, $sentAt);
        $this->messages[] = $message;
        $this->recordEvent(new MessagePosted($this->id->toUuid(), $messageId->toUuid(), false));

        return $message;
    }

    /**
     * Authorship by a host team member. The use case is responsible for verifying
     * that `$authorUserId` belongs to the host team (the aggregate does not know team membership).
     */
    public function postHostMessage(MessageId $messageId, MessageBody $body, Uuid $authorUserId, \DateTimeImmutable $sentAt): Message
    {
        $message = Message::user($messageId, $body, $authorUserId, $sentAt);
        $this->messages[] = $message;
        $this->recordEvent(new MessagePosted($this->id->toUuid(), $messageId->toUuid(), false));

        return $message;
    }

    public function isGuest(Uuid $userId): bool
    {
        return $this->guestUserId->equals($userId);
    }

    public function getId(): ConversationId
    {
        return $this->id;
    }

    public function getReservationId(): Uuid
    {
        return $this->reservationId;
    }

    public function getAccommodationId(): Uuid
    {
        return $this->accommodationId;
    }

    public function getTeamId(): Uuid
    {
        return $this->teamId;
    }

    public function getGuestUserId(): Uuid
    {
        return $this->guestUserId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Message[] */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
