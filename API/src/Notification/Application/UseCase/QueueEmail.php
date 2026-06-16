<?php

declare(strict_types=1);

namespace App\Notification\Application\UseCase;

use App\Notification\Domain\Command\QueueEmailCommand;
use App\Notification\Domain\Entity\EmailAddress;
use App\Notification\Domain\Entity\OutboxEmail;
use App\Notification\Domain\Port\EmailOutbox;
use App\Notification\Domain\Port\EmailRenderer;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\UuidGenerator;

/**
 * Renders the email's HTML view and persists it into the transactional outbox. The actual
 * sending is performed later by the relay (see {@see SendPendingEmails}). The HTML is frozen
 * at enqueue time so the outbox row is a self-contained, sendable artifact.
 */
final readonly class QueueEmail
{
    public function __construct(
        private EmailOutbox $outbox,
        private EmailRenderer $renderer,
        private Clock $clock,
    ) {
    }

    public function handle(QueueEmailCommand $command): void
    {
        $recipient = new EmailAddress($command->recipientEmail);
        $htmlBody = $this->renderer->renderHtml($command->template, $command->variables);

        $email = OutboxEmail::queue(
            id: UuidGenerator::generate(),
            recipient: $recipient,
            recipientName: $command->recipientName,
            subject: $command->subject,
            htmlBody: $htmlBody,
            createdAt: $this->clock->now(),
        );

        $this->outbox->save($email);
    }
}
