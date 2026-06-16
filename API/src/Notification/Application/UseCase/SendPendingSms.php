<?php

declare(strict_types=1);

namespace App\Notification\Application\UseCase;

use App\Notification\Domain\Exception\SmsDeliveryException;
use App\Notification\Domain\Port\SmsOutbox;
use App\Notification\Domain\Port\SmsSender;
use App\Shared\Domain\Port\Clock;

/**
 * The SMS outbox relay: reads pending SMS and attempts delivery, recording the outcome of
 * each. Designed to be run repeatedly (cron / scheduler / worker).
 */
final readonly class SendPendingSms
{
    public function __construct(
        private SmsOutbox $outbox,
        private SmsSender $sender,
        private Clock $clock,
        private int $batchSize = 50,
        private int $maxAttempts = 5,
    ) {
    }

    public function handle(): int
    {
        $messages = $this->outbox->findPending($this->batchSize);
        $sent = 0;

        foreach ($messages as $sms) {
            try {
                $this->sender->send($sms->toMessage());
                $sms->markSent($this->clock->now());
                ++$sent;
            } catch (SmsDeliveryException $exception) {
                $sms->recordFailedAttempt($exception->getMessage(), $this->clock->now(), $this->maxAttempts);
            }

            $this->outbox->save($sms);
        }

        return $sent;
    }
}
