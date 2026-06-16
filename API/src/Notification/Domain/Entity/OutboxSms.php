<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use Symfony\Component\Uid\Uuid;

/**
 * An SMS persisted in the transactional outbox. A listener queues it (status Pending) within
 * the same unit of work; the relay later sends it and records the outcome. Same reliability
 * guarantees as {@see OutboxEmail}.
 */
final class OutboxSms
{
    private function __construct(
        private readonly Uuid $id,
        private readonly PhoneNumber $recipient,
        private readonly string $text,
        private OutboxStatus $status,
        private int $attempts,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $lastAttemptAt,
        private ?string $error,
    ) {
    }

    public static function queue(Uuid $id, PhoneNumber $recipient, string $text, \DateTimeImmutable $createdAt): self
    {
        return new self(
            id: $id,
            recipient: $recipient,
            text: $text,
            status: OutboxStatus::Pending,
            attempts: 0,
            createdAt: $createdAt,
            lastAttemptAt: null,
            error: null,
        );
    }

    /**
     * Rehydration constructor for the persistence adapter.
     */
    public static function fromState(
        Uuid $id,
        PhoneNumber $recipient,
        string $text,
        OutboxStatus $status,
        int $attempts,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $lastAttemptAt,
        ?string $error,
    ): self {
        return new self(
            id: $id,
            recipient: $recipient,
            text: $text,
            status: $status,
            attempts: $attempts,
            createdAt: $createdAt,
            lastAttemptAt: $lastAttemptAt,
            error: $error,
        );
    }

    public function toMessage(): SmsMessage
    {
        return new SmsMessage($this->recipient, $this->text);
    }

    public function markSent(\DateTimeImmutable $sentAt): void
    {
        $this->status = OutboxStatus::Sent;
        ++$this->attempts;
        $this->lastAttemptAt = $sentAt;
        $this->error = null;
    }

    public function recordFailedAttempt(string $error, \DateTimeImmutable $attemptedAt, int $maxAttempts): void
    {
        ++$this->attempts;
        $this->lastAttemptAt = $attemptedAt;
        $this->error = $error;

        if ($this->attempts >= $maxAttempts) {
            $this->status = OutboxStatus::Failed;
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRecipient(): PhoneNumber
    {
        return $this->recipient;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getStatus(): OutboxStatus
    {
        return $this->status;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastAttemptAt(): ?\DateTimeImmutable
    {
        return $this->lastAttemptAt;
    }

    public function getError(): ?string
    {
        return $this->error;
    }
}
