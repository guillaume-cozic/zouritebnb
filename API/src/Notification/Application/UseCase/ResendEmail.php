<?php

declare(strict_types=1);

namespace App\Notification\Application\UseCase;

use App\Notification\Domain\Command\ResendEmailCommand;
use App\Notification\Domain\Exception\EmailDeliveryException;
use App\Notification\Domain\Exception\OutboxEmailNotFoundException;
use App\Notification\Domain\Port\EmailOutbox;
use App\Notification\Domain\Port\EmailSender;
use App\Shared\Domain\Port\Clock;

/**
 * Re-sends a single outbox email identified by its id, whatever its current status
 * (typically a dead-lettered "failed" one, but also a "sent" one to send again). The
 * outcome is recorded on the outbox just like a normal relay run.
 *
 * @throws OutboxEmailNotFoundException when no email matches the id
 * @throws EmailDeliveryException       when delivery fails (the failed attempt is persisted first)
 */
final readonly class ResendEmail
{
    public function __construct(
        private EmailOutbox $outbox,
        private EmailSender $sender,
        private Clock $clock,
        private int $maxAttempts = 5,
    ) {
    }

    public function handle(ResendEmailCommand $command): void
    {
        $email = $this->outbox->findById($command->emailId)
            ?? throw OutboxEmailNotFoundException::withId($command->emailId);

        try {
            $this->sender->send($email);
            $email->markSent($this->clock->now());
        } catch (EmailDeliveryException $exception) {
            $email->recordFailedAttempt($exception->getMessage(), $this->clock->now(), $this->maxAttempts);
            $this->outbox->save($email);

            throw $exception;
        }

        $this->outbox->save($email);
    }
}
