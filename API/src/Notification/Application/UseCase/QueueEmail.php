<?php

declare(strict_types=1);

namespace App\Notification\Application\UseCase;

use App\Notification\Domain\Command\QueueEmailCommand;
use App\Notification\Domain\Entity\EmailAddress;
use App\Notification\Domain\Entity\OutboxEmail;
use App\Notification\Domain\Port\EmailOutbox;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\UuidGenerator;

/**
 * Persists an email into the transactional outbox. The actual sending is performed
 * later by the relay (see {@see SendPendingEmails}).
 */
final readonly class QueueEmail
{
    public function __construct(
        private EmailOutbox $outbox,
        private Clock $clock,
    ) {
    }

    public function handle(QueueEmailCommand $command): void
    {
        $email = OutboxEmail::queue(
            id: UuidGenerator::generate(),
            recipient: new EmailAddress($command->recipientEmail),
            recipientName: $command->recipientName,
            subject: $command->subject,
            htmlBody: $command->htmlBody,
            createdAt: $this->clock->now(),
        );

        $this->outbox->save($email);
    }
}
