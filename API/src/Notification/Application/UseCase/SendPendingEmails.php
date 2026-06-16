<?php

declare(strict_types=1);

namespace App\Notification\Application\UseCase;

use App\Notification\Domain\Exception\EmailDeliveryException;
use App\Notification\Domain\Port\EmailOutbox;
use App\Notification\Domain\Port\EmailSender;
use App\Shared\Domain\Port\Clock;

/**
 * The outbox relay: reads pending emails and attempts delivery, recording the outcome
 * of each. Designed to be run repeatedly (cron / scheduler / worker). Each email is
 * saved independently so one failure never blocks the rest of the batch.
 */
final readonly class SendPendingEmails
{
    public function __construct(
        private EmailOutbox $outbox,
        private EmailSender $sender,
        private Clock $clock,
        private int $batchSize = 50,
        private int $maxAttempts = 5,
    ) {
    }

    public function handle(): int
    {
        $emails = $this->outbox->findPending($this->batchSize);
        $sent = 0;

        foreach ($emails as $email) {
            try {
                $this->sender->send($email);
                $email->markSent($this->clock->now());
                ++$sent;
            } catch (EmailDeliveryException $exception) {
                $email->recordFailedAttempt($exception->getMessage(), $this->clock->now(), $this->maxAttempts);
            }

            $this->outbox->save($email);
        }

        return $sent;
    }
}
