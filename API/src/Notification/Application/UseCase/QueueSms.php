<?php

declare(strict_types=1);

namespace App\Notification\Application\UseCase;

use App\Notification\Domain\Command\QueueSmsCommand;
use App\Notification\Domain\Entity\OutboxSms;
use App\Notification\Domain\Entity\PhoneNumber;
use App\Notification\Domain\Port\SmsOutbox;
use App\Shared\Domain\Port\Clock;
use App\Shared\Domain\Port\UuidGenerator;

/**
 * Persists an SMS into the transactional outbox. Actual sending is performed later by the
 * relay (see {@see SendPendingSms}).
 */
final readonly class QueueSms
{
    public function __construct(
        private SmsOutbox $outbox,
        private Clock $clock,
    ) {
    }

    public function handle(QueueSmsCommand $command): void
    {
        $sms = OutboxSms::queue(
            id: UuidGenerator::generate(),
            recipient: new PhoneNumber($command->recipientPhone),
            text: $command->text,
            createdAt: $this->clock->now(),
        );

        $this->outbox->save($sms);
    }
}
