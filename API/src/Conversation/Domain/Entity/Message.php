<?php

declare(strict_types=1);

namespace App\Conversation\Domain\Entity;

use App\Conversation\Domain\Exception\InvalidMessageException;
use Symfony\Component\Uid\Uuid;

final readonly class Message
{
    public function __construct(
        private MessageId $id,
        private ?MessageBody $body,
        private ?Uuid $authorUserId,
        private \DateTimeImmutable $sentAt,
        private bool $isSystem,
        private ?MessageAttachment $attachment = null,
    ) {
        if (null === $this->body && null === $this->attachment) {
            throw InvalidMessageException::becauseBodyNull();
        }
    }

    public static function user(MessageId $id, ?MessageBody $body, Uuid $authorUserId, \DateTimeImmutable $sentAt, ?MessageAttachment $attachment = null): self
    {
        return new self($id, $body, $authorUserId, $sentAt, false, $attachment);
    }

    public static function system(MessageId $id, MessageBody $body, \DateTimeImmutable $sentAt): self
    {
        return new self($id, $body, null, $sentAt, true);
    }

    public function getId(): MessageId
    {
        return $this->id;
    }

    public function getBody(): ?MessageBody
    {
        return $this->body;
    }

    public function getAttachment(): ?MessageAttachment
    {
        return $this->attachment;
    }

    public function getAuthorUserId(): ?Uuid
    {
        return $this->authorUserId;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }
}
