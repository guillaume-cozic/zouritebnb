<?php

declare(strict_types=1);

namespace App\Contact\Domain\Entity;

use App\Contact\Domain\Event\ContactMessageSent;
use App\Contact\Domain\Exception\InvalidContactMessageException;
use App\Shared\Domain\Entity\AggregateRoot;
use Symfony\Component\Uid\Uuid;

final class ContactMessage extends AggregateRoot
{
    public function __construct(
        private readonly Uuid $id,
        private readonly string $name,
        private readonly string $email,
        private readonly string $subject,
        private readonly string $message,
        private readonly \DateTimeImmutable $sentAt,
    ) {
        if ('' === trim($this->name)) {
            throw InvalidContactMessageException::becauseEmptyName();
        }
        if (false === filter_var($this->email, \FILTER_VALIDATE_EMAIL)) {
            throw InvalidContactMessageException::becauseInvalidEmail($this->email);
        }
        if ('' === trim($this->subject)) {
            throw InvalidContactMessageException::becauseEmptySubject();
        }
        if ('' === trim($this->message)) {
            throw InvalidContactMessageException::becauseEmptyMessage();
        }
    }

    /**
     * A visitor sends a contact message to the platform.
     */
    public static function send(
        Uuid $id,
        string $name,
        string $email,
        string $subject,
        string $message,
        \DateTimeImmutable $sentAt,
    ): self {
        $contactMessage = new self(
            id: $id,
            name: $name,
            email: $email,
            subject: $subject,
            message: $message,
            sentAt: $sentAt,
        );
        $contactMessage->recordEvent(new ContactMessageSent(contactMessageId: $id));

        return $contactMessage;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }
}
