<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\UseCase;

use App\Notification\Application\UseCase\ResendEmail;
use App\Notification\Domain\Command\ResendEmailCommand;
use App\Notification\Domain\Entity\EmailAddress;
use App\Notification\Domain\Entity\EmailStatus;
use App\Notification\Domain\Entity\OutboxEmail;
use App\Notification\Domain\Exception\EmailDeliveryException;
use App\Notification\Domain\Exception\OutboxEmailNotFoundException;
use App\Tests\Unit\Notification\Infrastructure\FakeEmailSender;
use App\Tests\Unit\Notification\Infrastructure\FixedClock;
use App\Tests\Unit\Notification\Infrastructure\InMemoryEmailOutbox;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ResendEmailTest extends TestCase
{
    private InMemoryEmailOutbox $outbox;
    private FixedClock $clock;

    #[Before]
    public function initOutbox(): void
    {
        $this->outbox = new InMemoryEmailOutbox();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-06-16 12:00:00'));
    }

    public function test_should_resend_a_failed_email_and_mark_it_sent(): void
    {
        $email = $this->deadLetteredEmail('01961e2f-beef-7000-dead-000000000001');
        $sender = new FakeEmailSender();

        (new ResendEmail($this->outbox, $sender, $this->clock))->handle(new ResendEmailCommand($email->getId()));

        self::assertCount(1, $sender->sent);
        $stored = $this->outbox->findById($email->getId());
        self::assertSame(EmailStatus::Sent, $stored?->getStatus());
        self::assertEquals($this->clock->now(), $stored->getLastAttemptAt());
    }

    public function test_should_record_the_failed_attempt_and_rethrow_when_delivery_fails(): void
    {
        $email = $this->deadLetteredEmail('01961e2f-beef-7000-dead-000000000002');
        $sender = new FakeEmailSender(shouldFail: true);
        $useCase = new ResendEmail($this->outbox, $sender, $this->clock, maxAttempts: 5);

        try {
            $useCase->handle(new ResendEmailCommand($email->getId()));
            self::fail('Expected EmailDeliveryException.');
        } catch (EmailDeliveryException) {
            // expected
        }

        $stored = $this->outbox->findById($email->getId());
        self::assertSame(2, $stored?->getAttempts());
        self::assertSame('Email delivery failed: transport down', $stored->getError());
    }

    public function test_should_throw_when_the_email_does_not_exist(): void
    {
        $this->expectException(OutboxEmailNotFoundException::class);

        (new ResendEmail($this->outbox, new FakeEmailSender(), $this->clock))
            ->handle(new ResendEmailCommand(Uuid::v4()));
    }

    private function deadLetteredEmail(string $id): OutboxEmail
    {
        $email = OutboxEmail::queue(
            id: Uuid::fromString($id),
            recipient: new EmailAddress('marie@example.com'),
            recipientName: 'Marie',
            subject: 'Sujet',
            htmlBody: '<p>Corps</p>',
            createdAt: new \DateTimeImmutable('2026-06-16 09:00:00'),
        );
        // Simulate an email that already exhausted the relay's automatic retries.
        $email->recordFailedAttempt('transport down', new \DateTimeImmutable('2026-06-16 09:05:00'), 1);
        $this->outbox->save($email);

        return $email;
    }
}
