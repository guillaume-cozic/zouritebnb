<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entity;

use Symfony\Component\Uid\Uuid;

/**
 * An email persisted in the transactional outbox. A listener reacting to a domain
 * event queues the email (status Pending) within the same unit of work; a separate
 * relay process later sends it and records the outcome. This decouples "we decided to
 * send this email" from "the SMTP call succeeded", giving at-least-once delivery and
 * resilience to mail-server outages.
 */
final class OutboxEmail
{
    private function __construct(
        private readonly Uuid $id,
        private readonly EmailAddress $recipient,
        private readonly ?string $recipientName,
        private readonly string $subject,
        private readonly string $htmlBody,
        private EmailStatus $status,
        private int $attempts,
        private readonly \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $lastAttemptAt,
        private ?string $error,
    ) {
    }

    public static function queue(
        Uuid $id,
        EmailAddress $recipient,
        ?string $recipientName,
        string $subject,
        string $htmlBody,
        \DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            recipient: $recipient,
            recipientName: $recipientName,
            subject: $subject,
            htmlBody: $htmlBody,
            status: EmailStatus::Pending,
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
        EmailAddress $recipient,
        ?string $recipientName,
        string $subject,
        string $htmlBody,
        EmailStatus $status,
        int $attempts,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $lastAttemptAt,
        ?string $error,
    ): self {
        return new self(
            id: $id,
            recipient: $recipient,
            recipientName: $recipientName,
            subject: $subject,
            htmlBody: $htmlBody,
            status: $status,
            attempts: $attempts,
            createdAt: $createdAt,
            lastAttemptAt: $lastAttemptAt,
            error: $error,
        );
    }

    public function markSent(\DateTimeImmutable $sentAt): void
    {
        $this->status = EmailStatus::Sent;
        ++$this->attempts;
        $this->lastAttemptAt = $sentAt;
        $this->error = null;
    }

    /**
     * Records a failed send attempt. The email stays Pending (and will be retried by the
     * next relay run) until it has exhausted $maxAttempts, after which it becomes Failed
     * (terminal / dead-lettered) so it stops being picked up.
     */
    public function recordFailedAttempt(string $error, \DateTimeImmutable $attemptedAt, int $maxAttempts): void
    {
        ++$this->attempts;
        $this->lastAttemptAt = $attemptedAt;
        $this->error = $error;

        if ($this->attempts >= $maxAttempts) {
            $this->status = EmailStatus::Failed;
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRecipient(): EmailAddress
    {
        return $this->recipient;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    public function getStatus(): EmailStatus
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
