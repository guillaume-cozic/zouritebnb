<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\UseCase;

use App\Notification\Application\UseCase\SendPendingEmails;
use App\Notification\Domain\Entity\EmailAddress;
use App\Notification\Domain\Entity\EmailStatus;
use App\Notification\Domain\Entity\OutboxEmail;
use App\Tests\Unit\Notification\Infrastructure\FakeEmailSender;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryEmailOutbox;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SendPendingEmailsTest extends TestCase
{
    private InMemoryEmailOutbox $outbox;
    private FixedClock $clock;

    #[Before]
    public function initOutbox(): void
    {
        $this->outbox = new InMemoryEmailOutbox();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-06-16 10:00:00'));
    }

    public function test_should_send_pending_emails_and_mark_them_sent(): void
    {
        $email = $this->queueEmail('01961e2f-beef-7000-dead-000000000001');
        $sender = new FakeEmailSender();

        $sent = (new SendPendingEmails($this->outbox, $sender, $this->clock))->handle();

        self::assertSame(1, $sent);
        self::assertCount(1, $sender->sent);
        $stored = $this->outbox->findById($email->getId());
        self::assertSame(EmailStatus::Sent, $stored?->getStatus());
        self::assertSame(1, $stored->getAttempts());
        self::assertEquals($this->clock->now(), $stored->getLastAttemptAt());
    }

    public function test_should_keep_email_pending_after_a_transient_failure(): void
    {
        $email = $this->queueEmail('01961e2f-beef-7000-dead-000000000002');
        $sender = new FakeEmailSender(shouldFail: true);

        $sent = (new SendPendingEmails($this->outbox, $sender, $this->clock, maxAttempts: 5))->handle();

        self::assertSame(0, $sent);
        $stored = $this->outbox->findById($email->getId());
        self::assertSame(EmailStatus::Pending, $stored?->getStatus());
        self::assertSame(1, $stored->getAttempts());
        self::assertSame('Email delivery failed: transport down', $stored->getError());
    }

    public function test_should_dead_letter_email_once_max_attempts_reached(): void
    {
        $email = $this->queueEmail('01961e2f-beef-7000-dead-000000000003');
        $sender = new FakeEmailSender(shouldFail: true);

        (new SendPendingEmails($this->outbox, $sender, $this->clock, maxAttempts: 1))->handle();

        $stored = $this->outbox->findById($email->getId());
        self::assertSame(EmailStatus::Failed, $stored?->getStatus());
        self::assertSame(1, $stored->getAttempts());
    }

    public function test_should_respect_the_batch_size(): void
    {
        $this->queueEmail('01961e2f-beef-7000-dead-000000000004');
        $this->queueEmail('01961e2f-beef-7000-dead-000000000005');
        $sender = new FakeEmailSender();

        $sent = (new SendPendingEmails($this->outbox, $sender, $this->clock, batchSize: 1))->handle();

        self::assertSame(1, $sent);
        self::assertCount(1, $this->outbox->findPending(10));
    }

    private function queueEmail(string $id): OutboxEmail
    {
        $email = OutboxEmail::queue(
            id: Uuid::fromString($id),
            recipient: new EmailAddress('marie@example.com'),
            recipientName: 'Marie',
            subject: 'Sujet',
            htmlBody: '<p>Corps</p>',
            createdAt: new \DateTimeImmutable('2026-06-16 09:00:00'),
        );
        $this->outbox->save($email);

        return $email;
    }
}
