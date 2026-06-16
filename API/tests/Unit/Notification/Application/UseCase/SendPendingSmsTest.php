<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\UseCase;

use App\Notification\Application\UseCase\SendPendingSms;
use App\Notification\Domain\Entity\OutboxSms;
use App\Notification\Domain\Entity\OutboxStatus;
use App\Notification\Domain\Entity\PhoneNumber;
use App\Tests\Unit\Notification\Infrastructure\FakeSmsSender;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemorySmsOutbox;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendPendingSmsTest extends TestCase
{
    private InMemorySmsOutbox $outbox;
    private FixedClock $clock;

    #[Before]
    public function initOutbox(): void
    {
        $this->outbox = new InMemorySmsOutbox();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-06-17 10:00:00'));
    }

    public function test_should_send_pending_sms_and_mark_them_sent(): void
    {
        $sms = $this->queue('01961e2f-beef-7000-dead-000000000001');
        $sender = new FakeSmsSender();

        $sent = (new SendPendingSms($this->outbox, $sender, $this->clock))->handle();

        self::assertSame(1, $sent);
        self::assertCount(1, $sender->sent);
        self::assertSame('+23057654321', $sender->sent[0]->getRecipient()->toString());
        self::assertSame(OutboxStatus::Sent, $this->outbox->findById($sms->getId())?->getStatus());
    }

    public function test_should_keep_sms_pending_after_a_transient_failure(): void
    {
        $sms = $this->queue('01961e2f-beef-7000-dead-000000000002');
        $sender = new FakeSmsSender(shouldFail: true);

        $sent = (new SendPendingSms($this->outbox, $sender, $this->clock, maxAttempts: 5))->handle();

        self::assertSame(0, $sent);
        $stored = $this->outbox->findById($sms->getId());
        self::assertSame(OutboxStatus::Pending, $stored?->getStatus());
        self::assertSame(1, $stored->getAttempts());
        self::assertSame('SMS delivery failed: gateway down', $stored->getError());
    }

    public function test_should_dead_letter_sms_once_max_attempts_reached(): void
    {
        $sms = $this->queue('01961e2f-beef-7000-dead-000000000003');
        $sender = new FakeSmsSender(shouldFail: true);

        (new SendPendingSms($this->outbox, $sender, $this->clock, maxAttempts: 1))->handle();

        self::assertSame(OutboxStatus::Failed, $this->outbox->findById($sms->getId())?->getStatus());
    }

    private function queue(string $id): OutboxSms
    {
        $sms = OutboxSms::queue(
            id: Uuid::fromString($id),
            recipient: new PhoneNumber('+23057654321'),
            text: 'Nouvelle demande de réservation',
            createdAt: new \DateTimeImmutable('2026-06-17 09:00:00'),
        );
        $this->outbox->save($sms);

        return $sms;
    }
}
